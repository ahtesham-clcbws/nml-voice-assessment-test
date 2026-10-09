<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    // Clear any cached models
    Cache::forget('gemini_models');
    
    // Set dummy API key
    putenv('GEMINI_API_KEY=fake-key');

    // Mock config to ensure a known test
    config(['audio_tests' => [
        [
            'id' => 'test-1',
            'language' => 'English',
            'skill' => 'Reading',
            'title' => 'Sample Test',
            'text' => 'Sample text to read',
            'gemini_context' => 'Sample context'
        ]
    ]]);
});

test('it renders the index page successfully', function () {
    // Mock the models API
    Http::fake([
        'https://generativelanguage.googleapis.com/v1beta/models*' => Http::response([
            'models' => [
                [
                    'name' => 'models/gemini-1.5-flash',
                    'displayName' => 'Gemini 1.5 Flash',
                    'supportedGenerationMethods' => ['generateContent'],
                    'inputTokenLimit' => 1048576,
                ]
            ]
        ], 200)
    ]);

    $response = $this->get('/gemini-tests');
    $response->dump();
    $response->assertSee('Sample Test');
    $response->assertSee('Gemini Model');
});

test('it validates missing audio file and test id', function () {
    $response = $this->postJson('/gemini-tests/process', []);
    
    $response->assertStatus(422)
             ->assertJsonValidationErrors(['test_id', 'audio']);
});

test('it requires a valid test id', function () {
    $audio = UploadedFile::fake()->create('test.mp3', 100, 'audio/mpeg');

    $response = $this->postJson('/gemini-tests/process', [
        'test_id' => 'invalid-test',
        'audio' => $audio
    ]);
    
    $response->assertStatus(400)
             ->assertJson(['error' => 'Invalid test ID']);
});

test('it processes the audio successfully with mocked gemini response', function () {
    $audio = UploadedFile::fake()->create('test.mp3', 100, 'audio/mpeg');

    Http::fake([
        // Mock generateContent endpoint
        'https://generativelanguage.googleapis.com/v1beta/models/*:generateContent*' => Http::response([
            'candidates' => [
                [
                    'finishReason' => 'STOP',
                    'content' => [
                        'parts' => [
                            ['text' => 'Good pronunciation.']
                        ]
                    ]
                ]
            ]
        ], 200),
        
        // Mock Models Endpoint
        'https://generativelanguage.googleapis.com/v1beta/models*' => Http::response([
            'models' => [
                [
                    'name' => 'models/gemini-1.5-flash',
                    'displayName' => 'Gemini 1.5 Flash',
                    'supportedGenerationMethods' => ['generateContent'],
                    'inputTokenLimit' => 1048576,
                ]
            ]
        ], 200)
    ]);

    $response = $this->postJson('/gemini-tests/process', [
        'test_id' => 'test-1',
        'audio' => $audio
    ]);
    
    $response->dump()
             ->assertJson([
                 'success' => true,
                 'model' => 'gemini-1.5-flash'
             ]);
});

test('it handles gemini api safety block gracefully', function () {
    $audio = UploadedFile::fake()->create('test.mp3', 100, 'audio/mpeg');

    Http::fake([
        'https://generativelanguage.googleapis.com/v1beta/models/*:generateContent*' => Http::response([
            'candidates' => [
                [
                    'finishReason' => 'SAFETY',
                    'content' => []
                ]
            ]
        ], 200),
        'https://generativelanguage.googleapis.com/v1beta/models*' => Http::response([
            'models' => [
                [
                    'name' => 'models/gemini-1.5-flash',
                    'supportedGenerationMethods' => ['generateContent'],
                    'inputTokenLimit' => 1048576,
                ]
            ]
        ], 200)
    ]);

    $response = $this->postJson('/gemini-tests/process', [
        'test_id' => 'test-1',
        'audio' => $audio
    ]);
    
    $response->assertStatus(400)
             ->assertJson(['error' => 'The audio was blocked by Gemini safety filters.']);
});

test('it allows manual model selection', function () {
    $audio = UploadedFile::fake()->create('test.mp3', 100, 'audio/mpeg');

    Http::fake([
        'https://generativelanguage.googleapis.com/v1beta/models/*:generateContent*' => Http::response([
            'candidates' => [
                [
                    'finishReason' => 'STOP',
                    'content' => [
                        'parts' => [['text' => 'Good']]
                    ]
                ]
            ]
        ], 200),
        'https://generativelanguage.googleapis.com/v1beta/models*' => Http::response([
            'models' => [
                [
                    'name' => 'models/gemini-1.5-flash',
                    'displayName' => 'Gemini 1.5 Flash',
                    'supportedGenerationMethods' => ['generateContent'],
                    'inputTokenLimit' => 1048576,
                ],
                [
                    'name' => 'models/gemini-1.5-pro',
                    'displayName' => 'Gemini 1.5 Pro',
                    'supportedGenerationMethods' => ['generateContent'],
                    'inputTokenLimit' => 2097152,
                ]
            ]
        ], 200)
    ]);

    $response = $this->postJson('/gemini-tests/process', [
        'test_id' => 'test-1',
        'model' => 'gemini-1.5-pro',
        'audio' => $audio
    ]);
    
    $response->dump()
             ->assertJson([
                 'success' => true,
                 'model' => 'gemini-1.5-pro'
             ]);
});
