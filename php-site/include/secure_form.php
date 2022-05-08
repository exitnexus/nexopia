<?
function hex2bin($data) {
	$len = strlen($data);
	$newdata = '';
	for($i=0;$i<$len;$i+=2) {
		$newdata .= pack("C",hexdec(substr($data,$i,2)));
	}
	return $newdata;
}

define("AES_IV", hex2bin('00000000000000000000000000000000'));
define("AES_KEY", '582f4a3f597466723838496a3e472374');

function secure_form_encrypt($uid, $timestamp) {
	return secure_form_encrypt_string("$uid:$timestamp");
}

function secure_form_encrypt_string($str) {
	return bin2hex(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, AES_KEY, $str, MCRYPT_MODE_ECB, AES_IV));
}