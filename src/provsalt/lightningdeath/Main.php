<?php

namespace provsalt\lightningdeath;

use pocketmine\entity\Entity;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use BlockHorizons\Fireworks\entity;
use pocketmine\math\Vector3;
use pocketmine\utils\Config;
use JackMD\UpdateNotifier\UpdateNotifier;

class Main extends PluginBase implements Listener {
    public $cfg;
    public function onEnable()
    {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->saveResource("config.yml");
        $this->cfg = new Config($this->getDataFolder() . "config.yml", Config::YAML);
        if ($this->cfg->get("version") !== 1){
            $this->getLogger()->critical("Please regenerate your config file!");
            $this->getServer()->getPluginManager()->disablePlugin($this);
        }
        UpdateNotifier::checkUpdate($this, $this->getDescription()->getName(), $this->getDescription()->getVersion());
    }
    public function onDeath(PlayerDeathEvent $event) :bool{
        if ($event->getPlayer()->hasPermission("lightningdeath.bypass")){
            return true;
        }
        // Create the type of firework item to be launched
/** @var Fireworks $fw */
$fw = ItemFactory::get(Item::FIREWORKS);
$fw->addExplosion(Fireworks::TYPE_CREEPER_HEAD, Fireworks::COLOR_GREEN, "", false, false);
$fw->setFlightDuration(2);

// Use whatever level you'd like here. Must be loaded
$level = Server::getInstance()->getDefaultLevel();
// Choose some coordinates
$vector3 = $level->getSpawnLocation()->add(0.5, 1, 0.5);
// Create the NBT data
$nbt = FireworksRocket::createBaseNBT($vector3, new Vector3(0.001, 0.05, 0.001), lcg_value() * 360, 90);
// Construct and spawn
$entity = FireworksRocket::createEntity("FireworksRocket", $level, $nbt, $fw);
if ($entity instanceof FireworksRocket) {
    $entity->spawnToAll();
}
        return true;
    }
    public function Lightning(Player $player) :void {
        $inworld = false;
        foreach ($this->cfg->get("worlds") as $worlds){
            if ($player->getLevel() === $this->getServer()->getLevelByName($worlds)){
                $inworld = true;
                break;
            }
        }
        if($inworld) {
            $light = $player->getLevel();
            $light = new AddActorPacket();
            $light->type = 93;
            $light->entityRuntimeId = Entity::$entityCount++;
            $light->metadata = array();
            $light->motion = null;
            $light->yaw = $player->getYaw();
            $light->pitch = $player->getPitch();
            $light->position = new Vector3($player->getX(), $player->getY(), $player->getZ());
            $this->getServer()->broadcastPacket($player->getLevel()->getPlayers(), $light);
            $sound = new PlaySoundPacket();
            $sound->x = $player->getX();
            $sound->y = $player->getY();
            $sound->z = $player->getZ();
            $sound->volume = 3;
            $sound->pitch = 2;
            $sound->soundName = "AMBIENT.WEATHER.LIGHTNING.IMPACT";
            $this->getServer()->broadcastPacket($player->getLevel()->getPlayers(), $sound);
        }
    }
}
