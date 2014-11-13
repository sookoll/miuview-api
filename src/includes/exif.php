<?php
/*
 * Exif Reader Class
 * author: Mihkel Oviir
 * 15 December 2009
 * sookoll@yahoo.com
 */

class exif {
	var $picture,$exif;
	var $tags = array(
		'FILE',
		'COMPUTED',
		'IFD0',
		'EXIF',
		'GPS',
		// FILE
		'FileSize',
		'MimeType',
		// COMPUTED
		'Width',
		'Height',
		// IDF0
		'Make',
		'Model',
		'Orientation',
		// EXIF
		'ExposureTime',
		'FNumber',
		'ISOSpeedRatings',
		'DateTimeOriginal',
		'DateTimeDigitized',
		'SpectralSensitivity',
		'ApertureValue',
		'BrightnessValue',
		'ExposureBiasValue',
		'MaxApertureValue',
		'ShutterSpeedValue',
		'SubjectDistance',
		'MeteringMode',
		'LightSource',
		'Flash',
		'FocalLength',
		'DigitalZoomRatio',
		'FocalLengthIn35mmFilm',
		'Contrast',
		'Saturation',
		'Sharpness',
		// GPS
		'GPSLatitudeRef',
		'GPSLatitude',
		'GPSLongitudeRef',
		'GPSLongitude',
		'GPSAltitudeRef',
		'GPSAltitude',
		'GPSTimeStamp',
		'GPSStatus',
		'GPSMeasureMode',
		'GPSDOP',
		'GPSSpeedRef',
		'GPSSpeed',
		'GPSTrackRef',
		'GPSTrack',
		'GPSImgDirectionRef',
		'GPSImgDirection'
	);

	// constructor
	public function __construct($picture){
		$this->picture=$picture;
		$this->readExif();
	}
	
	private function imageSize($item){
		return getimagesize($item);
	}

	private function readExif(){
		if(@exif_read_data($this->picture, 'IFD0'))
			$this->exif = exif_read_data($this->picture, 0, true);
		else {
			$is = $this->imageSize($this->picture);
			$this->exif['COMPUTED']['Width'] = $is[0];
			$this->exif['COMPUTED']['Height'] = $is[1];
			$this->exif['FILE']['MimeType'] = $is['mime'];
			$this->exif['FILE']['FileSize'] = filesize($this->picture);
		}
	}

	private function toFloat($s){
		list($a,$b)=explode('/',$s);
		if(!empty($a) || !empty($b))
			return floatval(trim($a))/floatval(trim($b));
		else return $s;
	}
	
	private function metaData($ar){
		$tmp=array();
		if(is_array($ar)){
			foreach($ar as $k=>$v){
				if(in_array($k,$this->tags)){
					switch($k){
						case 'COMPUTED':
							continue 2;
						case 'FILE':
							$tmp[$k]['Width'] = $ar['COMPUTED']['Width'];
							$tmp[$k]['Height'] = $ar['COMPUTED']['Height'];
							$tmp[$k]['FileSize'] = $v['FileSize'];
							$tmp[$k]['MimeType'] = $v['MimeType'];
						break;
						default:
							if(is_array($v)){
								foreach($v as $vk=>$vv){
									if(in_array($vk,$this->tags)){
										switch($vk){
											case 'GPSLatitudeRef':
											case 'GPSLongitudeRef':
											case 'GPSAltitudeRef':
												continue 2;
											break;
											case 'GPSLatitude':
											case 'GPSLongitude':
												$ref = ($v[$vk.'Ref'] == 'N') || ($v[$vk.'Ref'] == 'E')?1:-1;
												$tmp[$k][$vk]=$ref*($this->toFloat($vv[0])+($this->toFloat($vv[1])/60)+($this->toFloat($vv[2])/3600));
											break;
											case 'ExposureTime':
												list($a,$b)=explode('/',$vv);
												$b=intval(trim($b))/intval(trim($a));
												$a=intval(trim($a))/intval(trim($a));
												$tmp[$k][$vk] = $a.'/'.$b.' s';
											break;
											case 'FNumber':
											case 'ExposureBiasValue':
											case 'MaxApertureValue':
											case 'FocalLength':
											case 'DigitalZoomRatio':
											case 'ShutterSpeedValue':
											case 'ApertureValue':
											case 'GPSAltitude':
												$tmp[$k][$vk] = $this->toFloat($vv);
											break;
											default:
												$tmp[$k][$vk] = $vv;
											break;
										}
									}
								}
							}
						break;
					}
					
					
				}
			}
		}
		return $tmp;
	}
	
	public function getExif(){
		if(count($this->exif)>0){
			$this->exif = $this->metaData($this->exif);
			return $this->exif;
		}
		else
			return false;
	}
	
}
?>