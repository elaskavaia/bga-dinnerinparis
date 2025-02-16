<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace AppGame\Entity;

use AppGame\Game\AbstractTokenList;
use AppGame\Game\GridLocatable;
use AppGame\Service\BoardGameApp;

class Token extends AbstractEntity implements GridLocatable {
	
	/** @var int */
	private $type;
	
	/**
	 * Is available ? Used ?
	 *
	 * @var int
	 */
	//	private $state = 0;
	
	/** @var int|null */
	private $variant = null;
	
	/** @var int */
	private $container;
	
	/** @var int */
	private $position = 0;
	
	/** @var int|null */
	private $orientation = null;
	
	/** @var int|null */
	private $playerId = null;
	
	/** @var int */
	private $parentTokenId = null;
	
	/** @var AbstractTokenList|null */
	private $list = null;// Must have a list when used in php
	
	public function getSize(): int {
		return 1;
	}
	
	public function getMaterial(): array {
		return BoardGameApp::get()->getTokenMaterial($this);
	}
	
	public function getKey(): string {
		// Most of the materials is having a key but not all
		return $this->getMaterial()['key'];
	}
	
	public function getLabel(): string {
		return $this->getMaterial()['label'];
	}
	
	/**
	 * @param Player|null $player Null if spectator
	 * @return bool
	 */
	public function isPlayerVisible(?Player $player = null): bool {
		$playerId = $this->getPlayerId();
		$isOwner = $player && $playerId === $player->getId();
		$container = $this->getContainer();
		$visible = false;
		switch( $this->getType() ) {
			case TOKEN_TYPE_RESOURCE_CARD:
				if( in_array($container, [TOKEN_CONTAINER_BOARD_RIVER, TOKEN_CONTAINER_BOARD_DISCARD], true) ) {
					$visible = true;
				} elseif( $container === TOKEN_CONTAINER_PLAYER_HAND && $isOwner ) {
					$visible = true;
				}
				break;
			case TOKEN_TYPE_PIGEON_CARD:
				if( $container === TOKEN_CONTAINER_PLAYER_DISCARD ) {
					$visible = true;
				} elseif( $container === TOKEN_CONTAINER_PLAYER_HAND && $isOwner ) {
					$visible = true;
				}
				break;
			case TOKEN_TYPE_OBJECTIVE_CARD:
				if( in_array($container, [TOKEN_CONTAINER_BOARD_RIVER, TOKEN_CONTAINER_PLAYER_DISCARD], true) ) {
					$visible = true;
				} elseif( in_array($container, [TOKEN_CONTAINER_PLAYER_HAND, TOKEN_CONTAINER_PLAYER_BOARD], true) && $isOwner ) {
					$visible = true;
				}
				break;
			default:
				$visible = true;
				break;
		}
		
		return $visible;
	}
	
	public static function getMapping(): array {
		// Require all fields
		return [
			'id'              => 'id',
			'type'            => 'type',
			'variant'         => 'variant',
			//			'state'           => 'state',
			'container'       => 'container',
			'position'        => 'position',
			'orientation'     => 'orientation',
			'player_id'       => 'playerId',
			'parent_token_id' => 'parentTokenId',
		];
	}
	
	public function jsonSerialize(): array {
		return parent::jsonSerialize() + [
				'type'          => $this->getType(),
				'variant'       => $this->getVariant(),
				'container'     => $this->getContainer(),
				'position'      => $this->getPosition(),
				'orientation'   => $this->getOrientation(),
				'playerId'      => $this->getPlayerId(),
				'parentTokenId' => $this->getParentTokenId(),
			];
	}
	
	/**
	 * @return int
	 */
	public function getType(): int {
		return $this->type;
	}
	
	/**
	 * @param int $type
	 */
	public function setType(int $type): void {
		$this->type = $type;
	}
	
	/**
	 * @return int|null
	 */
	public function getVariant(): ?int {
		return $this->variant;
	}
	
	/**
	 * @param int|null $variant
	 */
	public function setVariant(?int $variant): void {
		$this->variant = $variant;
	}
	
	/**
	 * @return int
	 */
	public function getState(): int {
		return $this->state;
	}
	
	/**
	 * @param int $state
	 */
	public function setState(int $state): void {
		$this->state = $state;
	}
	
	/**
	 * @return int
	 */
	public function getContainer(): int {
		return $this->container;
	}
	
	/**
	 * @param int $container
	 */
	public function setContainer(int $container): void {
		$this->container = $container;
	}
	
	/**
	 * @return array|null
	 */
	public function getPoint(): ?array {
		if( !$this->position ) {
			return null;
		}
		$y = intval($this->position / 20);// Return integer (while floor() is returning double)
		$x = $this->position % 20;
		
		return [$x, $y];
	}
	
	/**
	 * @param $x
	 * @param $y
	 * @return Token
	 */
	public function setCoordinates($x, $y): self {
		$this->position = $y * 20 + $x;
		
		return $this;
	}
	
	/**
	 * @return int
	 */
	public function getPosition(): int {
		return $this->position;
	}
	
	/**
	 * @param int $position
	 */
	public function setPosition(int $position): void {
		$this->position = $position;
	}
	
	/**
	 * @return int
	 */
	public function getOrientation(): ?int {
		return $this->orientation;
	}
	
	/**
	 * @param int|null $orientation
	 */
	public function setOrientation(?int $orientation): void {
		$this->orientation = $orientation;
	}
	
	/**
	 * @return int
	 */
	public function getPlayerId(): ?int {
		return $this->playerId;
	}
	
	/**
	 * @param int|null $playerId
	 */
	public function setPlayerId(?int $playerId): void {
		$this->playerId = $playerId;
	}
	
	public function setPlayer(?Player $player): void {
		$this->setPlayerId($player ? $player->getId() : null);
	}
	
	public function isOwnedBy(?Player $player): bool {
		return $player && $this->playerId === $player->getId();
	}
	
	public function containedBy(int $container): bool {
		return $this->container === $container;
	}
	
	/**
	 * @return int|null
	 */
	public function getParentTokenId(): ?int {
		return $this->parentTokenId;
	}
	
	/**
	 * @param int|null $parentTokenId
	 */
	public function setParentTokenId(?int $parentTokenId): void {
		$this->parentTokenId = $parentTokenId;
	}
	
	public function setParentToken(?Token $token): void {
		$this->setParentTokenId($token ? $token->getId() : null);
	}
	
	/**
	 * @return AbstractTokenList|null
	 */
	public function getList(): ?AbstractTokenList {
		return $this->list;
	}
	
	/**
	 * @param AbstractTokenList|null $list
	 */
	public function setList(?AbstractTokenList $list): void {
		$this->list = $list;
	}
	
	public static function getEntityTable(): ?string {
		return 'token';
	}
	
	public static function getAlternativeClass(array $data): string {
		if( isset($data['type']) ) {
			switch( intval($data['type']) ) {
				case TOKEN_TYPE_RESOURCE_CARD:
					return ResourceCard::class;
				case TOKEN_TYPE_TERRACE:
					return Terrace::class;
				case TOKEN_TYPE_RESTAURANT:
					return Restaurant::class;
				case TOKEN_TYPE_PIGEON_CARD:
					return PigeonCard::class;
			}
		}
		
		return self::class;
	}
	
}
