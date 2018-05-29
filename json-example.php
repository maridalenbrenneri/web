<?php

echo console_log('hello from php');

 $object = (object) [
    'propertyOne' => 'foo',
    'propertyTwo' => (object) ['nested' => 'bar'],
  ];

  $json = json_encode($object);
  echo '<pre>' . $json . '</pre>';

  $decoded = json_decode($json);
  echo 'propertyOne: ' . $decoded->propertyOne;
  echo ' propertyTwo.nested: ' . $decoded->propertyTwo->nested;

function console_log( $data ){
    echo '<script>';
    echo 'console.log('. json_encode( $data ) .')';
    echo '</script>';
  }

?>