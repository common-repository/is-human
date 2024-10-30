<?php
	#functions
	function hexrgb ($c) {
		 if(!$c) 
			 return false;
	 
		 $c = trim($c);
		 $out = false;
	 
		 if(eregi("^[0-9ABCDEFabcdef\#]+$", $c))
		 {
			 $c = str_replace('#','', $c);
			 $l = strlen($c);
			 if($l == 3)
			 {
				 unset($out);
				 $out[0] = $out['r'] = $out['red'] = hexdec(substr($c, 0,1));
				 $out[1] = $out['g'] = $out['green'] = hexdec(substr($c, 1,1));
				 $out[2] = $out['b'] = $out['blue'] = hexdec(substr($c, 2,1));
			 }
			 elseif($l == 6)
			 {
				 unset($out);
				 $out[0] = $out['r'] = $out['red'] = hexdec(substr($c, 0,2));
				 $out[1] = $out['g'] = $out['green'] = hexdec(substr($c, 2,2));
				 $out[2] = $out['b'] = $out['blue'] = hexdec(substr($c, 4,2));
			 }
			 else 
				 $out = false;
		 }
		 elseif (eregi("^[0-9]+(,| |.)+[0-9]+(,| |.)+[0-9]+$", $c))
		 {
			 if(eregi(",", $c))
			 $e = explode(",",$c);
			 else if(eregi(" ", $c))
			 $e = explode(" ",$c);
			 else if(eregi(".", $c))
			 $e = explode(".",$c);
			 else return false;
	 
			 if(count($e) != 3) 
				 return false;
	 
			 $out = '#';
			 for($i = 0; $i<3; $i++)
			 $e[$i] = dechex(($e[$i] <= 0)?0:(($e[$i] >= 255)?255:$e[$i]));
	 
			 for($i = 0; $i<3; $i++)
				 $out .= ((strlen($e[$i]) < 2)?'0':'').$e[$i];
	 
			 $out = strtoupper($out);
		 }
		 else 
			 $out = false;
	 
		 return $out;
	}
	header('Content-Type: image/jpeg');
	#captcha code
	session_start();
	$length = 8;
	$code = md5(sha1(uniqid(time(), true)));
	$code = strtoupper(substr($code, rand(0, strlen($code) - $length), $length));
	unset($_SESSION['__is_human_value']);
	$_SESSION['__is_human_value'] = $code;
	#image creation
	$width = $_GET['width'] ? $_GET['width'] : 100;
	$height = $_GET['height'] ? $_GET['height'] : 30;
	$img = imagecreate($width, $height);
	#allocate colors
	$font = hexrgb($_GET['font_color']);
	$bg = hexrgb($_GET['background']);
	$white = imagecolorallocate($img, 240, 240, 240);
	$grey = imagecolorallocate($img, $bg['r'], $bg['g'], $bg['b']);
	$black = imagecolorallocate($img, $font['r'], $font['g'], $font['b']);
	#make background
	imagefilledrectangle($img, 0, 0, $width, $height, $grey);
	#draw lines
	for ($i = 0; $i < 6; $i++) {
		$color = imagecolorallocate($img, rand(100, 250), rand(100, 250), rand(100, 250));
		imageline($img, rand(0, $width), rand(0, $height), rand(0, $width), rand(0, $height), $color);
	}
	$dimensions = imagettfbbox($_GET['font_size'], 0, 'fonts/' . $_GET['font'], $code);
	$x = ($width - ($dimensions[2] - $dimensions[0])) / 2; 
	$y = ($height - ($dimensions[5] - $dimensions[3])) / 2;
	imagettftext($img, $_GET['font_size'], 0, $x, $y, $black, 'fonts/' . $_GET['font'], $code);
	imagejpeg($img, false, 90);
?>