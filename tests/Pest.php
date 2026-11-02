<?php

use AiTests\TestCase;

pest()->extend(TestCase::class)->in('Feature');

dataset('storage', ['sql', 'mongodb']);
