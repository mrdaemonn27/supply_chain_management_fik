<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| Groq credentials must stay outside the repository. Configure
| GROQ_API_KEY in Apache/Windows and restart Apache after changing it.
*/
$config['api_key'] = trim((string) getenv('GROQ_API_KEY'));
$config['model'] = trim((string) getenv('GROQ_MODEL')) ?: 'qwen/qwen3.6-27b';
$config['endpoint'] = 'https://api.groq.com/openai/v1/chat/completions';
$config['connect_timeout'] = 5;
$config['request_timeout'] = 20;

