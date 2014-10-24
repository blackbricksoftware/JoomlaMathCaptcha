<?php defined('_JEXEC') or die;

class PlgCaptchaMathCaptcha extends JPlugin {

	public function onInit($id) {

		return true;
	}

	public function onDisplay($name, $id, $class='') {

		$session = JFactory::getSession();

		$vars = (array)$session->get('mathcaptcha');

		$min = (int)$this->params->get('min',1);
		$max = (int)$this->params->get('max',10);

		preg_match('/class="(.*?)"/',$class,$matches);
		if (empty($matches[1])) {
			$originalclasses = $class;
		} else {
			$originalclasses = $matches[1];
		}
		$additionalclasses = $this->params->get('additionalclasses','input-mini');
		$class = $originalclasses.' '.$additionalclasses;

		$vars[$uniqid=uniqid()] = (object)array(
			'low' => $low=rand($min,$max),
			'high' => $high=rand($min,$max),
		);
		$session->set('mathcaptcha',$vars);

		return $this->_txt2img('What does '.$low.' + '.$high.' = ').'
			<input type="text" size="2" class="'.htmlspecialchars($class).'" name="'.htmlspecialchars($name).'[value]"  id="'.htmlspecialchars($id).'" placeholder="Answer" />
			<input type="hidden" name="'.htmlspecialchars($name).'[uniqid]" value="'.$uniqid.'" />
		';
	}

	public function onCheckAnswer($code) {

		$app = JFactory::getApplication();
		$session = JFactory::getSession();

		$code = (object)$code;
		$vars = $session->get('mathcaptcha');

		$success = array_key_exists($code->uniqid,$vars) &&
			($vars[$code->uniqid]->low+$vars[$code->uniqid]->high)==(int)$code->value;

		if (!$success) $app->enqueueMessage('Math captcha answer was incorrect.','warning');

		return $success;
	}

	private function _txt2img($string) {

		$fontfile = __DIR__.'/fonts/FreeSans.ttf';

		$font = (int)$this->params->get('fontsize',14);
		$im = @imagecreatetruecolor(strlen($string) * $font / 1.70, $font);
		imagesavealpha($im, true);
		imagealphablending($im, false);
		$white = imagecolorallocatealpha($im, 255, 255, 255, 127);
		imagefill($im, 0, 0, $white);
		$rgb = $this->_hex2rgb($this->params->get('textcolor', '#000000'));
		//~ $alpha = (int)$this->params->get('alpha', '127');
		$color = imagecolorallocate($im, $rgb[0], $rgb[1], $rgb[2]);
		imagettftext($im, $font, 0, 1, $font, $color, $fontfile, $string);
		ob_start(); imagepng($im); $image = ob_get_clean();

		imagedestroy($im);

		ob_start(); ?>
		<img src="data:image/png;base64,<?= base64_encode($image) ?>" />
		<?php return ob_get_clean();
	}

	// http://bavotasan.com/2011/convert-hex-color-to-rgb-using-php/
	private function _hex2rgb($hex) {
		$hex = str_replace("#", "", $hex);

		if(strlen($hex) == 3) {
			$r = hexdec(substr($hex,0,1).substr($hex,0,1));
			$g = hexdec(substr($hex,1,1).substr($hex,1,1));
			$b = hexdec(substr($hex,2,1).substr($hex,2,1));
		} else {
			$r = hexdec(substr($hex,0,2));
			$g = hexdec(substr($hex,2,2));
			$b = hexdec(substr($hex,4,2));
		}
		$rgb = array($r, $g, $b);
		//return implode(",", $rgb); // returns the rgb values separated by commas
		return $rgb; // returns an array with the rgb values
	}
}
