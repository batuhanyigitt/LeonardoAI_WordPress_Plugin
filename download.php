<?php
if(empty($_GET['f']))
{
    exit();
}

$fileName   = explode('/', $_GET['f']);
$fileName   = end($fileName); 

if(empty($fileName))
{
    $fileName = rand(10000000, 9000000000000000000000).'.jpg';
}

$remoteFile = file_get_contents($_GET['f']);

$filePath   = dirname(__FILE__)."/temp/$fileName";

file_put_contents( $filePath, $remoteFile);



//file path in server
$file_path = dirname(__FILE__).'/inc/ccc.jpg';//$_GET['f'];

header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="'.basename($filePath).'"');
header('Expires: 0');
header('Pragma: public');
header('Content-Length: ' . filesize($filePath));

// Clear output buffer
flush();
readfile($filePath);
@unlink($filePath);
exit();