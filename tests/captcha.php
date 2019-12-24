<?php
$phpfiledir = pathinfo(__FILE__)["dirname"].DIRECTORY_SEPARATOR;
require_once $phpfiledir.'../vendor/autoload.php';
use Gregwar\Captcha\CaptchaBuilder;
use Gregwar\Captcha\PhraseBuilder;

$phraseBuilder = new PhraseBuilder(6);
$builder = new CaptchaBuilder(null, $phraseBuilder);

// $builder = new CaptchaBuilder;
$builder->build();

// $builder->save('out.jpg');
header('Content-type: image/jpeg');
$builder->output();

// $phrase = $builder->getPhrase();

// if($builder->testPhrase($userInput)) {

// } else {

// }
?>