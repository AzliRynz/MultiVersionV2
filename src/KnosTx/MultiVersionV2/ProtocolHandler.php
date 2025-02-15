<?php

declare(strict_types=1);

namespace KnosTx\MultiVersionV2;

use pocketmine\utils\Filesystem;

class ProtocolHandler {

    private Main $plugin;
    private ConfigLoader $configLoader;
    private array $data = [];
    private array $defaultData = [];

    public function __construct(Main $plugin, ConfigLoader $configLoader) {
        $this->plugin = $plugin;
        $this->configLoader = $configLoader;
        $this->loadDefaultData();
    }

    private function loadDefaultData(): void {
        $defaultFile = $this->plugin->getDataFolder() . "default.json";
        
        if (!file_exists($defaultFile)) {
            $this->plugin->getLogger()->warning("Default data file not found: default.json");
            $this->defaultData = [];
            return;
        }

        $jsonData = file_get_contents($defaultFile);
        $this->defaultData = json_decode($jsonData, true) ?? [];

        if ($this->defaultData === null) {
            $this->plugin->getLogger()->warning("Failed to parse default.json, using empty data.");
            $this->defaultData = [];
        }
    }

    public function loadDataForProtocol(int $protocol): bool {
        $versionMap = $this->configLoader->getVersionMap();
        
        if (!isset($versionMap[$protocol])) {
            $this->plugin->getLogger()->warning("No mapping found for protocol {$protocol}, using default data.");
            $this->data = $this->defaultData;
            return false;
        }

        $fileName = $versionMap[$protocol];
        $filePath = $this->plugin->getDataFolder() . $fileName;

        if (!file_exists($filePath)) {
            $this->plugin->getLogger()->warning("File {$fileName} not found, using default data.");
            $this->data = $this->defaultData;
            return false;
        }

        $jsonData = file_get_contents($filePath);
        $decodedData = json_decode($jsonData, true) ?? $this->defaultData;

        if ($decodedData === null) {
            $this->plugin->getLogger()->warning("Failed to parse {$fileName}, using default data.");
            $this->data = $this->defaultData;
            return false;
        }

        $this->data = $decodedData;
        return true;
    }

    public function getRuntimeId(string $key): ?int {
        return $this->data[$key]["runtime_id"] ?? null;
    }
}
