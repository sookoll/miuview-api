<?php
/*
 * Miuview API
 * class to resize images
 * 
 * Creator: Mihkel Oviir
 * 08.2011
 * 
 */

class resizeImage{
	
	var $modes = array('longest','width','height','square');
	var $src_path;
	var $src_img;
	var $src_info;
	var $th_img;
	var $th_size;
	var $th_mode;
	var $th_crop;
	var $th_quality;
	var $th_origin;

	// constructor
	public function __construct($srcimg){
		$this->src_path = $srcimg;
		ini_set("memory_limit","512M");
		
		$is = $this->imageSize($this->src_path);
		$this->src_info['width'] = $is[0];
		$this->src_info['height'] = $is[1];
		$this->src_info['mime'] = $is['mime'];
	}
	
	public function __get($name) {
        return $name ?? null;
    }
	
	private function getExtension($item) {
		return strtolower(substr($item, strrpos($item, '.') + 1));
	}
	
	private function getRawImage($src,$ext){
		// create image based on format
		switch($ext){
			case 'jpg':
			case 'jpeg':
				$src = imagecreatefromjpeg($src);
			break;
			case 'png':
				$src = imagecreatefrompng($src);
				imagealphablending($src, true);
			break;
			case 'gif':
				$src = imagecreatefromgif($src);
			break;
			default:
				exit();
			break;
		}
		return $src;
	}
	
	private function imageSize($item){
		return getimagesize($item);
	}
	
	public function setThumbParams($size = 100, $mode = 'longest', $crop = 0, $quality = 75) {
		// only allowed modes
		if (!in_array($mode, $this->modes, true)) {
            $mode = 'longest';
        }
		$this->th_mode = $mode;
		
		// set needed size
		//switch by mode
		switch($this->th_mode) {
			case 'longest':
				//find biggest length
				$longest = max($this->src_info['width'], $this->src_info['height']);
			break;
			case 'width':
				$longest = $this->src_info['width'];
			break;
			case 'height':
				$longest = $this->src_info['height'];
			break;
			case 'square':
				$longest = min($this->src_info['width'], $this->src_info['height']);
			break;
		} // switch

        if (isset($longest)) {
            $this->th_size['init'] = min($longest, $size);
        }

		
		// hold crop % between 0 and 75
		if($crop<0) {
            $crop = 0;
        }
		if($crop>75) {
            $crop = 75;
        }
		$this->th_crop = $crop;
		
		// hold quality % between 0 and 100
		if($quality<0) {
            $quality = 0;
        }
		if($quality>100) {
            $quality = 100;
        }
		$this->th_quality = $quality;
		
		//setting the top left coordinate
		$this->th_origin['x'] = 0;
		$this->th_origin['y'] = 0;
	}
	
	private function calculateThumbParams(){
		//switch by mode
		switch($this->th_mode){
			case 'longest':
				//find biggest length
				if ($this->src_info['width']>=$this->src_info['height']) {
                    list($this->th_size['width'], $this->th_size['height']) = $this->calculator($this->th_size['init'], $this->src_info['width'], $this->src_info['height']);
                } else {
                    list($this->th_size['height'], $this->th_size['width']) = $this->calculator($this->th_size['init'], $this->src_info['height'], $this->src_info['width']);
                }
			break;
			case 'width':
				list($this->th_size['width'],$this->th_size['height'])=$this->calculator($this->th_size['init'],$this->src_info['width'],$this->src_info['height']);
			break;
			case 'height':
				list($this->th_size['height'],$this->th_size['width'])=$this->calculator($this->th_size['init'],$this->src_info['height'],$this->src_info['width']);
			break;
			case 'square':
				//find biggest length
				if ($this->src_info['width']>=$this->src_info['height']) {
					//getting the top left coordinate
					$this->th_origin['x'] = ($this->src_info['width']-$this->src_info['height'])/2;
					$this->src_info['width']=$this->src_info['height'];
				} else {
					//getting the top left coordinate
					$this->th_origin['y'] = ($this->src_info['height']-$this->src_info['width'])/2;
					$this->src_info['height']=$this->src_info['width'];
				}
				$this->th_size['width'] = $this->th_size['height'] = $this->th_size['init'];
			break;
		} // switch

		// if crop not 0, then calculate new image dimensions and top-left point
		if ($this->th_crop !== 0) {
			//getting the new dimensions
			$cropPercent = 1-$this->th_crop/100;
			$cropWidth = $this->src_info['width']*$cropPercent;
			$cropHeight = $this->src_info['height']*$cropPercent;

			//getting the top left coordinate
			$this->th_origin['x'] = ($this->src_info['width']-$cropWidth)/2;
			$this->th_origin['y'] = ($this->src_info['height']-$cropHeight)/2;

			$this->src_info['width']=$cropWidth;
			$this->src_info['height']=$cropHeight;
		}
	}

	// create new image
	function createThumb() {
		$this->src_info['ext'] = $this->getExtension($this->src_path);
		$this->src_img = $this->getRawImage($this->src_path,$this->src_info['ext']);
		
		$this->calculateThumbParams();
		
		$this->th_img = imagecreatetruecolor($this->th_size['width'],$this->th_size['height']);
		imagealphablending($this->th_img, false);
		imagesavealpha($this->th_img, true);
		
		imagecopyresampled(
			$this->th_img,
			$this->src_img,
			0,
			0,
			$this->th_origin['x'],
			$this->th_origin['y'],
			$this->th_size['width'],
			$this->th_size['height'],
			$this->src_info['width'],
			$this->src_info['height']
		);
		imageinterlace($this->th_img,1);

	}// create new image

	// output
	function outputImage($target = '') {

		// if not save to image, show on fly
		if($target === '') {
			header('Content-type: '.$this->src_info['mime']);
			$format = $this->src_info['ext'];
			
			// output image based on format
			switch($format){
				case 'jpg':
				case 'jpeg':
					imagejpeg($this->th_img,null,$this->th_quality);
				break;
				case 'png':
					imagepng($this->th_img);
				break;
				case 'gif':
					imagegif($this->th_img);
				break;
				default:
					exit();
			}

		//detect new image format
		} else {
			$format = $this->getExtension($target);
			
			// output image based on format
			switch($format){
				case 'jpg':
				case 'jpeg':
					imagejpeg($this->th_img,$target,$this->th_quality);
				break;
				case 'png':
					imagepng($this->th_img,$target);
				break;
				case 'gif':
					imagegif($this->th_img,$target);
				break;
				default:
					exit();
			}
		}

		// delete tmp image
		imagedestroy($this->th_img);
		
	} // output
	
	private function calculator($a,$b,$c) {
		$r[0] = $a;
		$r[1] = ($a / $b) * $c;

		return $r;
	}
	
} // class ResizeImage
