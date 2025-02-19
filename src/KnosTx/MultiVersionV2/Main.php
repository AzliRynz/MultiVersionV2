<?php

declare(strict_types=1);

namespace KnosTx\MultiVersionV2;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\utils\TextFormat;

class Main extends PluginBase implements Listener {

    private ProtocolHandler $protocolHandler;
    private ConfigLoader $configLoader;
    private PlayerManager $playerManager;

    public function onEnable(): void {
        $this->saveDefaultResources();

        $this->configLoader = new ConfigLoader($this);
        $this->protocolHandler = new ProtocolHandler($this, $this->configLoader);
        $this->playerManager = new PlayerManager($this->protocolHandler, $this->configLoader);

        $this->getServer()->getPluginManager()->registerEvents($this, $this);

        $supported = implode(", ", $this->configLoader->getSupportedProtocols());
        $this->getLogger()->info(TextFormat::GREEN . "Supported Protocols: " . $supported);
    }

    private function saveDefaultResources(): void {
        foreach (["config.yml", "default.json"] as $resource) {
            if (!file_exists($this->getDataFolder() . $resource)) {
                $this->saveResource($resource);
            }
        }
    }

    public function onDataPacketReceive(DataPacketReceiveEvent $event): void {
        $packet = $event->getPacket();
        if ($packet instanceof LoginPacket) {
            $protocol = $packet->protocol;
            $session = $event->getOrigin();
            $playerName = $session->getDisplayName();

            if ($this->protocolHandler->loadDataForProtocol($protocol)) {
                $this->getLogger()->info("Player {$playerName} joined with protocol {$protocol}.");
            } else {
                $this->getLogger()->warning("Unsupported protocol {$protocol} for {$playerName}, using default data.");
            }
        }
    }

    public function onPlayerJoin(PlayerJoinEvent $event): void {
        $player = $event->getPlayer();
        $this->playerManager->handlePlayerJoin($player);
    }
}
