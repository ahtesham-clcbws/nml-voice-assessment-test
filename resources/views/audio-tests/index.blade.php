<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audio Testing Suite</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        [x-cloak] { display: none !important; }
        .spinner {
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            width: 1.5rem;
            height: 1.5rem;
            animation: spin 1s ease-in-out infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .small-spinner {
            border: 2px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #3B82F6;
            width: 1rem;
            height: 1rem;
            animation: spin 1s ease-in-out infinite;
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-800 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 py-8">
        
        <!-- Navigation -->
        <nav class="flex space-x-4 mb-6 border-b border-gray-200 pb-4">
            <a href="/gemini-playground" class="text-gray-500 hover:text-gray-700 font-medium">Free-form Playground</a>
            <a href="/gemini-tests" class="text-blue-600 font-medium border-b-2 border-blue-600 pb-4 -mb-4">Audio Testing Suite (Batch)</a>
        </nav>

        <header class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Audio Testing Suite</h1>
            <p class="text-gray-600 mt-2">Select a predefined test, assign student names, upload audio files, and evaluate using Gemini in batch.</p>
        </header>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-8">
            <!-- Left Column: Input Form -->
            <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 h-fit">
                
                <form id="testForm" class="space-y-6">
                    @csrf
                    
                    <!-- Filters -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Language</label>
                            <select id="languageSelect" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm border p-2">
                                <option value="">Select Language</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Skill</label>
                            <select id="skillSelect" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm border p-2">
                                <option value="">Select Skill</option>
                            </select>
                        </div>
                    </div>

                    <!-- Test Selection -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Predefined Test</label>
                        <select id="testSelect" name="test_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm border p-2">
                            <option value="">Select a test...</option>
                        </select>
                    </div>

                    <!-- Test Information Display -->
                    <div id="testInfo" class="hidden bg-blue-50 rounded-md p-4 border border-blue-100 space-y-3">
                        <div class="flex justify-between items-start">
                            <h3 id="infoTitle" class="text-md font-bold text-blue-900"></h3>
                            <span id="infoDifficulty" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-200 text-blue-800"></span>
                        </div>
                        
                        <div>
                            <h4 class="text-xs font-semibold text-blue-800 uppercase tracking-wider mb-1">Instructions</h4>
                            <p id="infoInstruction" class="text-sm text-blue-900 whitespace-pre-wrap"></p>
                        </div>
                        
                        <div>
                            <h4 class="text-xs font-semibold text-blue-800 uppercase tracking-wider mb-1">Text to Read</h4>
                            <div id="infoText" class="text-lg bg-white p-3 rounded border border-blue-200 text-gray-800 whitespace-pre-wrap leading-relaxed font-serif" dir="auto"></div>
                        </div>
                    </div>

                    <!-- Audio Inputs (Up to 5) -->
                    <div class="space-y-4 pt-4 border-t border-gray-200">
                        <label class="block text-sm font-bold text-gray-700">Student Uploads (Max 5)</label>
                        <p class="text-xs text-gray-500">Add a name and select an audio file for one or more students to evaluate them simultaneously.</p>
                        
                        @for($i = 1; $i <= 5; $i++)
                        <div class="p-3 bg-gray-50 border border-gray-200 rounded-md student-row">
                            <div class="flex flex-col md:flex-row gap-3 items-center">
                                <div class="w-full md:w-1/3">
                                    <input type="text" id="studentName_{{ $i }}" placeholder="Student {{ $i }} Name" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm border p-2 student-name">
                                </div>
                                <div class="w-full md:w-2/3">
                                    <input type="file" id="audioUpload_{{ $i }}" accept="audio/*" class="block w-full text-sm text-gray-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-blue-100 file:text-blue-700 hover:file:bg-blue-200 student-audio">
                                </div>
                            </div>
                        </div>
                        @endfor
                    </div>

                    <div class="pt-4 border-t border-gray-200">
                        <button type="submit" id="submitBtn" disabled class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition">
                            <span id="submitText">Evaluate All with Gemini</span>
                            <div id="submitSpinner" class="hidden spinner ml-3"></div>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Right Column: Results Display -->
            <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 flex flex-col h-full min-h-[600px]">
                <h2 class="text-lg font-medium text-gray-900 border-b border-gray-200 pb-2 mb-4">Batch Evaluation Results</h2>
                
                <div id="emptyState" class="flex-1 flex items-center justify-center text-gray-400">
                    Select a test, upload student files, and submit to see results.
                </div>

                <div id="resultsContainer" class="flex-1 space-y-6 overflow-y-auto hidden">
                    <!-- Dynamic results will be injected here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Pass tests JSON to Javascript -->
    <script>
        const allTests = {!! json_encode($tests) !!};
    </script>
    
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            
            // DOM Elements
            const languageSelect = document.getElementById('languageSelect');
            const skillSelect = document.getElementById('skillSelect');
            const testSelect = document.getElementById('testSelect');
            
            const testInfo = document.getElementById('testInfo');
            const infoTitle = document.getElementById('infoTitle');
            const infoDifficulty = document.getElementById('infoDifficulty');
            const infoInstruction = document.getElementById('infoInstruction');
            const infoText = document.getElementById('infoText');

            const submitBtn = document.getElementById('submitBtn');

            // --- Populate Dropdowns ---
            const languages = [...new Set(allTests.map(t => t.language))];
            languages.forEach(l => {
                const opt = document.createElement('option');
                opt.value = l;
                opt.textContent = l;
                languageSelect.appendChild(opt);
            });

            function updateSkills() {
                skillSelect.innerHTML = '<option value="">Select Skill</option>';
                testSelect.innerHTML = '<option value="">Select a test...</option>';
                testInfo.classList.add('hidden');
                
                const lang = languageSelect.value;
                if (!lang) return;

                const skills = [...new Set(allTests.filter(t => t.language === lang).map(t => t.skill))];
                skills.forEach(s => {
                    const opt = document.createElement('option');
                    opt.value = s;
                    opt.textContent = s;
                    skillSelect.appendChild(opt);
                });
            }

            function updateTests() {
                testSelect.innerHTML = '<option value="">Select a test...</option>';
                testInfo.classList.add('hidden');

                const lang = languageSelect.value;
                const skill = skillSelect.value;
                if (!lang || !skill) return;

                const filtered = allTests.filter(t => t.language === lang && t.skill === skill);
                filtered.forEach(t => {
                    const opt = document.createElement('option');
                    opt.value = t.id;
                    opt.textContent = t.title;
                    testSelect.appendChild(opt);
                });
            }

            function checkSubmitState() {
                const hasTest = !!testSelect.value;
                let hasAudio = false;
                
                for(let i=1; i<=5; i++) {
                    const fileInput = document.getElementById('audioUpload_' + i);
                    if(fileInput.files.length > 0) {
                        hasAudio = true;
                        break;
                    }
                }

                if(hasTest && hasAudio) {
                    submitBtn.disabled = false;
                } else {
                    submitBtn.disabled = true;
                }
            }

            function updateTestInfo() {
                const testId = testSelect.value;
                if (!testId) {
                    testInfo.classList.add('hidden');
                    checkSubmitState();
                    return;
                }

                const test = allTests.find(t => t.id === testId);
                infoTitle.textContent = test.title;
                infoDifficulty.textContent = test.difficulty;
                infoInstruction.textContent = test.instruction;
                infoText.textContent = test.display_text;
                
                if(test.language === 'Urdu' || test.language === 'Arabic/Qaida') {
                    infoText.setAttribute('dir', 'rtl');
                    infoText.classList.add('text-right');
                } else {
                    infoText.setAttribute('dir', 'ltr');
                    infoText.classList.remove('text-right');
                }

                testInfo.classList.remove('hidden');
                checkSubmitState();
            }

            languageSelect.addEventListener('change', updateSkills);
            skillSelect.addEventListener('change', updateTests);
            testSelect.addEventListener('change', updateTestInfo);
            
            // Listen to file inputs
            for(let i=1; i<=5; i++) {
                document.getElementById('audioUpload_' + i).addEventListener('change', checkSubmitState);
            }

            // --- Form Submission Logic ---
            const form = document.getElementById('testForm');
            const submitText = document.getElementById('submitText');
            const submitSpinner = document.getElementById('submitSpinner');
            const emptyState = document.getElementById('emptyState');
            const resultsContainer = document.getElementById('resultsContainer');

            form.addEventListener('submit', async (e) => {
                e.preventDefault();

                const testId = testSelect.value;
                
                let studentsToProcess = [];
                for(let i=1; i<=5; i++) {
                    const fileInput = document.getElementById('audioUpload_' + i);
                    const nameInput = document.getElementById('studentName_' + i);
                    
                    if(fileInput.files.length > 0) {
                        studentsToProcess.push({
                            id: i,
                            name: nameInput.value.trim() || `Student ${i}`,
                            file: fileInput.files[0]
                        });
                    }
                }

                if (studentsToProcess.length === 0 || !testId) return;

                // UI Loading Setup
                submitBtn.disabled = true;
                submitText.textContent = 'Evaluating...';
                submitSpinner.classList.remove('hidden');
                
                emptyState.classList.add('hidden');
                resultsContainer.classList.remove('hidden');
                resultsContainer.innerHTML = ''; // Clear previous results

                // Create placeholder cards for each student
                const resultElements = {};
                studentsToProcess.forEach(student => {
                    const card = document.createElement('div');
                    card.className = "border border-gray-200 rounded-md bg-gray-50 overflow-hidden shadow-sm";
                    card.innerHTML = `
                        <div class="bg-gray-100 px-4 py-3 border-b border-gray-200 flex justify-between items-center">
                            <h4 class="font-bold text-gray-800">${student.name}</h4>
                            <div class="flex items-center text-xs text-blue-600 font-medium" id="status_${student.id}">
                                <div class="small-spinner mr-2"></div> Processing...
                            </div>
                        </div>
                        <div class="p-4" id="content_${student.id}">
                            <div class="animate-pulse flex space-x-4">
                                <div class="flex-1 space-y-4 py-1">
                                    <div class="h-2 bg-gray-300 rounded w-3/4"></div>
                                    <div class="space-y-2">
                                        <div class="h-2 bg-gray-300 rounded"></div>
                                        <div class="h-2 bg-gray-300 rounded w-5/6"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    resultsContainer.appendChild(card);
                    resultElements[student.id] = {
                        status: card.querySelector(`#status_${student.id}`),
                        content: card.querySelector(`#content_${student.id}`)
                    };
                });

                // Process concurrently
                const promises = studentsToProcess.map(async (student) => {
                    const formData = new FormData();
                    formData.append('test_id', testId);
                    formData.append('audio', student.file);

                    try {
                        const response = await fetch('/gemini-tests/process', {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                            }
                        });

                        const data = await response.json();
                        const elements = resultElements[student.id];

                        if (response.ok && data.success) {
                            elements.status.innerHTML = `<span class="text-green-600 flex items-center"><svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Done (${data.time})</span>`;
                            elements.content.innerHTML = `
                                <div class="prose prose-sm max-w-none text-gray-800 bg-white p-3 rounded border border-gray-200">
                                    ${data.html}
                                </div>
                            `;
                        } else {
                            elements.status.innerHTML = `<span class="text-red-600 flex items-center"><svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg> Error</span>`;
                            elements.content.innerHTML = `
                                <div class="bg-red-50 text-red-700 p-3 rounded border border-red-200 text-sm">
                                    ${data.error || 'Evaluation failed.'}
                                </div>
                            `;
                        }
                    } catch (error) {
                        const elements = resultElements[student.id];
                        elements.status.innerHTML = `<span class="text-red-600 flex items-center"><svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg> Error</span>`;
                        elements.content.innerHTML = `
                            <div class="bg-red-50 text-red-700 p-3 rounded border border-red-200 text-sm">
                                Network or server error: ${error.message}
                            </div>
                        `;
                    }
                });

                // Wait for all to finish
                await Promise.allSettled(promises);

                submitBtn.disabled = false;
                submitText.textContent = 'Evaluate All with Gemini';
                submitSpinner.classList.add('hidden');
            });
        });
    </script>
</body>
</html>
