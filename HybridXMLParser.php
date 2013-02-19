<?php
	/**
	* Hybrid XML parser
	*
	* Class to parse huge XML files in a memory-efficient way.
	*
	* @author Alexander Kovalenko <alexanderk23@gmail.com>
	* @link http://github.com/alexanderk23/HybridXMLParser
	* @license Public Domain
	*/

	if(!defined('LIBXML_PARSEHUGE')) {
		define(LIBXML_PARSEHUGE, 1<<19);
	}

	class HybridXMLParserException extends \Exception {}

	class HybridXMLParser {

		protected
			$xml,
			$uri,
			$path,
			$stop,
			$pathListeners = array(),
			$ignoreCase = false;

                /**
                * @param boolean $ignoreCase Ignore XML tags case
                */
		public function __construct($ignoreCase = true) {
			$this->xml = new \XMLReader;
			$this->ignoreCase = $ignoreCase;
		}

		protected function convertCase($s) {
			return $this->ignoreCase ? mb_strtolower($s) : $s;
		}

		/**
		* @param string $path XML path to watch for (slash-separated)
		* @param mixed $listener Callable (lambda, inline function, function name as string, or array(class, method))
		* @return HybridXMLParser
		*/
		public function bind($path, $listener) {
			if(!is_callable($listener)) {
				throw new InvalidArgumentException('Listener is not callable');
			}
			$path = $this->convertCase($path);
			if(isset($this->pathListeners[$path])) {
				throw new HybridXMLParserException('Another listener is already bound to path ' . $path);
			}
			$this->pathListeners[$path] = $listener;

			return $this;
		}

		public function unbind($path) {
			$path = $this->convertCase($path);
			if(isset($this->pathListeners[$path])) {
				unset($this->pathListeners[$path]);
			}

			return $this;
		}

		public function unbindAll() {
			$this->pathListeners = array();

			return $this;
		}

		public function stop() {
			$this->stop = true;

			return $this;
		}

		protected function getCurrentPath() {
			return '/' . join('/', $this->path);
		}

		protected function notifyListener($path) {
			if(isset($this->pathListeners[$path])) {
				$this->pathListeners[$path](
					new \SimpleXMLElement($this->xml->readOuterXML(), LIBXML_COMPACT | LIBXML_NOCDATA),
					$this
				);
			}

			return $this;
		}

		public function process($uri, $options = 0) {
			$this->path = array();

			if(!$this->xml->open($uri, NULL, $options | LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_PARSEHUGE)) {
				throw new HybridXMLParserException('Cannot open ' . $uri);
			}

			$this->stop = false;

			while(!$this->stop && $this->xml->read()) {
				switch($this->xml->nodeType) {
					case \XMLReader::ELEMENT:
						array_push($this->path, $this->convertCase($this->xml->name));
						$path = $this->getCurrentPath();
						$stop = $this->notifyListener($path);
						if(!$this->xml->isEmptyElement) {
							break;
						}
					case \XMLReader::END_ELEMENT:
						array_pop($this->path);
						break;
				}
			}

			$this->xml->close();

			return $this;
		}

	}

