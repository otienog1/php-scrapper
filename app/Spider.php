<?php

namespace App;

class Spider extends \DOMDocument
{
	private $_options = [];
	public $url;
	private $_already_crawled = [];
	private $_crawling = [];
	private $_anchorText = "";


	public function __construct()
	{
		$this->_options['http'] = [
			'method' => "GET",
			'headers' => "User-Agent: 7plus8-bot/0.1\n"
		];
	}

	public function crawl($url)
	{
		$this->url = $url;
		@$this->loadHTML(file_get_contents($this->url, false, stream_context_create($this->_options)));
		$links = $this->getElementsbyTagName("a");
		// var_dump($links);

		// $this->_anchorText = $links->item(0)->nodeValue;

		$this->parse($links);
	}

	public function parse($links)
	{
		foreach ($links as $link) {
			$href = $link->getAttribute("href");
			switch ($href) {
				case substr($href, 0, 1) == "/" && substr($href,0, 2) != "//" :
					$href = parse_url($this->url)["scheme"] . "://" . parse_url($this->url)["host"] . $href;
					break;
				
				case substr($href, 0, 2) == "//" :
					$href = parse_url($this->url)["scheme"] . ":" . $href;
					break;

				case substr($href, 0, 2) == "./" :
					$href = parse_url($this->url)["scheme"] . "://" . parse_url($this->url)["host"] . dirname(parse_url($this->url)["path"]) . substr($href, 1);
					break;

				case substr($href, 0, 1) == "#" :
					continue;
					// $href = parse_url($this->url)["scheme"] . "://" . parse_url($this->url)["host"] . $href;
					break;

				case  substr($href, 0, 3) == "../" :
					$href = parse_url($this->url)["scheme"] . "://" . parse_url($this->url)["host"] . "/" . $href;
					break;

				case substr($href, 0, 11) == "javascript:" :
					continue;
					break;

				case substr($href, 0, 5) != "https" && substr($href, 0, 4) != "http" :
					$href = parse_url($this->url)["scheme"] . "://" . parse_url($this->url)["host"] . "/" . $href;
					break;
			}

			if ( !in_array($href, $this->_already_crawled) ){

				$this->_already_crawled[] = $href;
				$this->_crawling[] = $href;
				echo $this->pageDetails($href) . "\n";

			}

		}

		array_shift($this->_crawling);

		foreach ($this->_crawling as $page) {

			$this->crawl($page);

		}
	}

	public function pageDetails($url)
	{
		$heading = [];
		$title = $this->getElementsbyTagName("title");
		// $heading['h1'] = $this->getElementsbyTagName("h1");
		// $heading['h2'] = $this->getElementsbyTagName("h2");
		// $heading['h3'] = $this->getElementsbyTagName("h3");
		// $heading['h4'] = $this->getElementsbyTagName("h4");
		$images = $this->getElementsbyTagName("img");

		$alt = "";
		$imagesrc = "";

		if (count($images)) {
			for ($i=0; $i < $images->length; $i++) { 
				$images = $images->item($i);
				$alt = $images->getAttribute('alt');
				$imagesrc = $images->getAttribute('src');
			}
		}

		// for ($i=0; $i < $heading->length; $i++) { 
		// 	var_dump($heading->item($i)->nodeValue);
		// }

		$title = $title->item(0)->nodeValue;

		$description = "";
		$keywords = "";

		$metas = $this->getElementsbyTagName("meta");

		for ($i=0; $i < $metas->length; $i++) {
			$meta = $metas->item($i);

			if (strtolower($meta->getAttribute("name")) == "description"){
				$description = $meta->getAttribute("content");
			}
			if (strtolower($meta->getAttribute("name")) == "keywords"){
				$keywords = $meta->getAttribute("content");
			}
		}
		return '{ "Title": "'.str_replace("\n", "", $title).'", "Description": "'.str_replace("\n", "", $description).'", "Keywords": "'.str_replace("\n", "", $keywords).'", "URL": "'.$url.'", "ImageAlt": "' . $alt . '", "ImageSrc": "' . $imagesrc . '"},';
	}
}