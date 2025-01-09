<?php

namespace App\Http\Controllers;

use App\Models\Flow;
use Illuminate\Http\Request;

class FlowController extends Controller
{
    public function index()
    {
        $flows = Flow::all();
        return response()->json($flows);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'nodes' => 'required|array',
            'connections' => 'array'
        ]);

        $flow = Flow::create($validated);

        return response()->json($flow, 201);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'nodes' => 'required|array',
            'connections' => 'array'
        ]);

        $flow = Flow::findOrFail($id);
        $flow->update($validated);

        return response()->json($flow, 200);
    }

    public function execute($id)
    {
        $flow = Flow::findOrFail($id);
        // Get sorted nodes based on connections
        $sortedNodes = $this->topologicalSort($flow->nodes, $flow->connections);

        $results = [];
        foreach ($sortedNodes as $node) {
            try {
                $input = $this->getNodeInput($node, $results);
                $results[$node['label']] = $this->executeNode($node, $input);
            } catch (\Exception $e) {
                return response()->json([
                    'error' => 'Flow execution failed',
                    'node' => $node['label'],
                    'message' => $e->getMessage()
                ], 500);
            }
        }

        return response()->json(['results' => $results]);
    }

    private function executeNode($node, $input)
    {
        switch ($node['label']) {
            case 'API Call':
                return $this->executeAPICall($node, $input);
            case 'ChatGPT':
                dd($input);
                return $this->executeChatGPT($node, $input);
            case 'Email':
                dd($input);
                return $this->executeEmail($node, $input);
            default:
                throw new \Exception("Unknown node type: {$node['label']}");
        }
    }

    private function executeAPICall($node, $input)
    {
        try {
            // Ensure method and URL are defined
            $method = $node['controls']['method']['value'] ?? 'GET';
            $url = $node['controls']['url']['value'] ?? '';

            if (empty($url)) {
                throw new \InvalidArgumentException("URL cannot be empty.");
            }

            // Initialize Guzzle client
            $client = new \GuzzleHttp\Client();

            // Make the request
            $response = $client->request($method, $url, [
                'json' => $input, // Send JSON data
            ]);
            // Check the response status code
            if ($response->getStatusCode() === 200) {
                return json_decode($response->getBody()->getContents(), true);
            } else {
                throw new \RuntimeException("Unexpected response status: " . $response->getStatusCode());
            }
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            // Catch Guzzle exceptions
            return [
                'error' => 'Request failed',
                'message' => $e->getMessage(),
                'response' => $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : null,
            ];
        } catch (\Exception $e) {
            // Catch other exceptions
            return [
                'error' => 'Execution failed',
                'message' => $e->getMessage(),
            ];
        }
    }

    private function executeChatGPT($node, $input)
    {
        try {
            $apiKey = config('services.openai.api_key');
            // Initialize OpenAI client
            $openai = \OpenAI::client($apiKey);

            // Make the chat request
            $response = $openai->chat()->create([
                'model' => $node['controls']['model']['value'],
                'messages' => [
                    ['role' => 'user', 'content' => $node['controls']['prompt']['value']]
                ],
                'temperature' => $node['controls']['temperature']['value']
            ]);
            return $response['choices'][0]['message']['content'] ?? ''; // Return the response
        } catch (\Exception $e) {
            // Handle exceptions
            return [
                'error' => 'ChatGPT execution failed',
                'message' => $e->getMessage(),
                'trace' => $e->getTrace(),
            ];
        }
    }

    private function executeEmail($node, $input)
    {
        \Mail::to($node['controls']['to']['value'])
            ->send(new \App\Mail\FlowEmail(
                $node['controls']['subject']['value'],
                $node['controls']['body']['value']
            ));

        return ['status' => 'sent'];
    }

    private function topologicalSort($nodes, $connections)
    {
        $inDegree = [];
        $graph = [];

        // Initialize in-degree and graph
        foreach ($nodes as $node) {
            $inDegree[$node['id']] = 0;
            $graph[$node['id']] = [];
        }
        // Build the graph and compute in-degrees of nodes
        foreach ($connections as $connection) {
            $from = $connection['source'];
            $to = $connection['target'];
            $graph[$from][] = $to;
            $inDegree[$to]++;
        }

        // Queue for nodes with no incoming edges
        $queue = [];
        foreach ($inDegree as $node => $degree) {
            if ($degree == 0) {
                $queue[] = $node;
            }
        }

        $sortedNodes = [];
        while (!empty($queue)) {
            $current = array_shift($queue);
            $sortedNodes[] = $current;

            foreach ($graph[$current] as $neighbor) {
                $inDegree[$neighbor]--;
                if ($inDegree[$neighbor] == 0) {
                    $queue[] = $neighbor;
                }
            }
        }

        // Check for cycles in the graph
        if (count($sortedNodes) !== count($nodes)) {
            throw new \Exception('Graph has at least one cycle');
        }

        // Map sorted node IDs back to node objects
        $sortedNodeObjects = array_map(function ($nodeId) use ($nodes) {
            return array_values(array_filter($nodes, function ($node) use ($nodeId) {
                return $node['id'] === $nodeId;
            }))[0];
        }, $sortedNodes);

        return $sortedNodeObjects;
    }

    private function getNodeInput($node, $results)
    {
        $input = [];

        // Gather inputs from connected nodes
        foreach ($node['inputs'] as $inputKey => $inputValue) {
            if (isset($inputValue['connections'])) {
                foreach ($inputValue['connections'] as $connection) {
                    $sourceNodeId = $connection['node'];
                    if (isset($results[$sourceNodeId])) {
                        $input[$inputKey] = $results[$sourceNodeId];
                    }
                }
            }
        }

        return $input;
    }
}
