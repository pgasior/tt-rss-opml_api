<?php
class Opml_API extends Plugin {

	/** @var  PluginHost $host */
	private $host;

	function init($host) {
		$this->host = $host;
		$this->host->add_api_method("importOPML", $this);
	}

	function about(): array
	{
		return array(1.0
		, "Plugin for importing OPML via API"
		, "pgasior"
		, true
		);
	}

	function api_version(): int {
		return 2;
	}

	function importOPML() {
		Debug::log("Test log");
		$opml = new OPML($_REQUEST);

		if (!isset($_REQUEST["opml"])) {
			return array(API::STATUS_ERR,
				array(
					"message" => "No opml passed"
				));
		}
		$opml_content = base64_decode($_REQUEST["opml"]);
		// Logger::log(E_USER_NOTICE, $opml_content);
		if ($opml_content == false) {
			return array(API::STATUS_ERR,
				array(
					"message" => "Invalid opml passed"
				));
		}

		$doc = new DOMDocument();
		// libxml_disable_entity_loader(false);
		$loaded = $doc->loadXML($opml_content);
		// libxml_disable_entity_loader(true);


		if ($loaded) {
			ob_start();
			// $this->pdo->beginTransaction();
			$method = new ReflectionMethod($opml, "opml_import_category");
			$method->setAccessible(true);
			$method->invoke($opml, $doc, null, $this->host->get_owner_uid(), 0, 0);
			// $this->pdo->commit();
			$val = ob_get_clean();

			$val = html_entity_decode($val);

			return array(API::STATUS_OK,
				array(
					"message" => array_filter(explode("<br/>", $val)),
					"duplicate_message" => substr(__("Duplicate feed: %s"), 0, -2),
					"added_message" => substr(__("Adding feed: %s"), 0, -2)
				));
		} else {
			return array(API::STATUS_ERR,
				array(
					"message" => 'Error while parsing document.'
				));
		}
	}
}
