<?php

namespace App\Http\Controllers;

use App\Domain;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use DiDom\Document;

class DomainController extends Controller
{
    private $client;
    private $document;

    public function __construct(Client $client, Document $document)
    {
        $this->client = $client;
        $this->document = $document;
    }

    public function index()
    {
        return view('form');
    }

    public function show($id)
    {
        $domain = Domain::find($id);
        return view('domain', ['domain' => $domain]);
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'url' => 'required|url'
        ]);

        $response = $this->client->request('GET', $request->input('url'));
        $contLength = $response->hasHeader('Content-Length') ?
            $response->getHeader('Content-Length')[0] : 0;
        $body = $response->getBody()->read(65500);

        $document = $this->document->create($body);
        $heading = $document->first('h1');
        $keywords = $document->first('meta[name=keywords]');
        $description = $document->first('meta[name=description]');

        $domain = Domain::create(['name' => $request->input('url'),
            'status' => $response->getReasonPhrase(),
            'code' => $response->getStatusCode(),
            'contLength' => $contLength,
            'body' => $body,
            'heading' => $heading ? $heading->text() : '_',
            'keyContent' => $keywords ? $keywords->getAttribute('content') : '_',
            'descContent' => $description ? $description->getAttribute('content') : '_',
        ]);
        return redirect(route('domains.show', ['id' => $domain->id]));
    }

    public function showDomains()
    {
        $domains = Domain::paginate(10);
        return view('domains', ['domains' => $domains]);
    }
}
