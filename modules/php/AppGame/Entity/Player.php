<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

namespace AppGame\Entity;

use DateTime;

/**
 * Sync with Player.js
 */
class Player extends AbstractEntity {
	
	const FLAG_TERRACE_PLACED = 'action_terrace';
	const FLAG_USED_PIGEON_CARD = 'pigeon';
	const FLAG_DRAW_RESOURCE_CARD = 'draw_resource_card';
	const FLAG_REQUEST_PIGEON_CARD_OBJECTIVE = 'request_pc_objective';// Request to play an objective pigeon card when checking objective complete action
	const FLAG_USE_PIGEON_CARD_OBJECTIVE = 'use_pc_objective';// Used one objective pigeon card to complete an objective during this action
	const FLAG_RESUME_PIGEON_CARD_DRAW_RESOURCE = 'resume_pc_draw_resource';
	const FLAG_RESUME_PIGEON_CARD_ADD_TERRACE = 'resume_pc_add_terrace';
	const FLAG_PENDING_PIGEON_CARD_ADJACENT_TERRACE = 'pending_pc_adjacent_terrace';// Wait for player to place a terrace to choose next restaurant
	const FLAG_PIGEON_CARD_ADJACENT_TERRACE_RESTAURANTS = 'pc_adjacent_terrace_restaurants';// Array of restaurant [id=>CoverLeft] to allow adjacent terrace and cover up
	const FLAG_PLAYING_PIGEON_CARD = 'playing_pigeon_card';// ID of playing pigeon card
	
	const COLOR_MAP = ['ff0000' => 'red', '00008B' => 'blue', 'ff8c00' => 'orange', '00ced1' => 'cyan'];
	
	/** @var int */
	private $position = 0;
	
	/** @var string */
	private $canal;
	
	/** @var string */
	private $name;
	
	/** @var string */
	private $avatar;
	
	/** @var string */
	private $color;
	
	/** @var int */
	private $score;
	
	/** @var int */
	private $scoreAux;
	
	/** @var bool */
	private $zombie;
	
	/** @var bool */
	private $bot;
	
	/** @var bool */
	private $eliminated;
	
	/** @var int */
	private $nextNotificationNumber;
	
	/** @var bool */
	private $enterGame;
	
	/** @var bool */
	private $overTime;
	
	/** @var bool */
	private $multiActive;
	
	/** @var bool */
	private $beginner;
	
	/** @var DateTime|null */
	private $startReflexionTime;
	
	/** @var int|null */
	private $remainingReflexionTime;
	
	/** @var int */
	private $income;
	
	/** @var int|null */
	private $balance;
	
	/** @var int|null */
	private $pendingIncome;
	
	/** @var array */
	private $turnFlags = [];
	
	/** @var array */
	private $turnData = [];
	
	/** @var array */
	private $actionFlags = [];
	
	/** @var array */
	private $majority = [];
	
	/** @var DateTime|null */
	private $majorityUpdateDate = null;
	
	public function getLabel(): string {
		return $this->name;
	}
	
	public function jsonSerialize(): array {
		return parent::jsonSerialize() + [
				'id'            => $this->getId(),
				'name'          => $this->getName(),
				'position'      => $this->getPosition(),
				'score'         => $this->getScore(),
				'color'         => $this->getColor(),
				'colorKey'      => $this->getColorKey(),
				'avatar'        => $this->getAvatar(),
				'income'        => $this->getIncome(),
				'balance'       => $this->getBalance(),
				'pendingIncome' => $this->getPendingIncome(),
				'turnFlags'     => $this->getTurnFlags(),
				'turnData'      => $this->getTurnFlags(),
				'actionFlags'   => $this->getActionFlags(),
				'majority'      => $this->getMajority(),
			];
	}
	
	public static function getMapping(): array {
		// Require all fields
		return [
			'player_id'                       => 'id',
			'player_no'                       => 'position',
			'player_canal'                    => 'canal',
			'player_name'                     => 'name',
			'player_avatar'                   => 'avatar',
			'player_color'                    => 'color',
			'player_score'                    => 'score',
			'player_score_aux'                => 'scoreAux',
			'player_zombie'                   => 'zombie',
			'player_ai'                       => 'bot',
			'player_eliminated'               => 'eliminated',
			'player_next_notif_no'            => 'nextNotificationNumber',
			'player_enter_game'               => 'enterGame',
			'player_over_time'                => 'overTime',
			'player_is_multiactive'           => 'multiActive',
			'player_start_reflexion_time'     => 'startReflexionTime',
			'player_remaining_reflexion_time' => 'remainingReflexionTime',
			'player_beginner'                 => 'beginner',
			'income'                          => 'income',
			'balance'                         => 'balance',
			'pending_income'                  => 'pendingIncome',
			'turn_flags'                      => 'turnFlags',
			'turn_data'                       => 'turnData',
			'action_flags'                    => 'actionFlags',
			'majority'                        => 'majority',
			'majority_update_date'            => 'majorityUpdateDate',
		];
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
	 * @return string
	 */
	public function getCanal(): string {
		return $this->canal;
	}
	
	/**
	 * @param string $canal
	 */
	public function setCanal(string $canal): void {
		$this->canal = $canal;
	}
	
	/**
	 * @return string
	 */
	public function getName(): string {
		return $this->name ?? 'Not loaded';
	}
	
	/**
	 * @param string $name
	 */
	public function setName(string $name): void {
		$this->name = $name;
	}
	
	/**
	 * @return string
	 */
	public function getAvatar(): string {
		return $this->avatar;
	}
	
	/**
	 * @param string $avatar
	 */
	public function setAvatar(string $avatar): void {
		$this->avatar = $avatar;
	}
	
	/**
	 * @return string
	 */
	public function getColor(): string {
		return $this->color;
	}
	
	/**
	 * @param string $color
	 */
	public function setColor(string $color): void {
		$this->color = $color;
	}
	
	/**
	 * @return int
	 */
	public function getScore(): int {
		return $this->score;
	}
	
	/**
	 * @param int $score
	 */
	public function setScore(int $score): void {
		$this->score = $score;
	}
	
	/**
	 * @return int
	 */
	public function getScoreAux(): int {
		return $this->scoreAux;
	}
	
	/**
	 * @param int $scoreAux
	 */
	public function setScoreAux(int $scoreAux): void {
		$this->scoreAux = $scoreAux;
	}
	
	/**
	 * @return bool
	 */
	public function isZombie(): bool {
		return $this->zombie;
	}
	
	/**
	 * @param bool $zombie
	 */
	public function setZombie(bool $zombie): void {
		$this->zombie = $zombie;
	}
	
	/**
	 * @return bool
	 */
	public function isBot(): bool {
		return $this->bot;
	}
	
	/**
	 * @param bool $bot
	 */
	public function setBot(bool $bot): void {
		$this->bot = $bot;
	}
	
	/**
	 * @return bool
	 */
	public function isEliminated(): bool {
		return $this->eliminated;
	}
	
	/**
	 * @param bool $eliminated
	 */
	public function setEliminated(bool $eliminated): void {
		$this->eliminated = $eliminated;
	}
	
	/**
	 * @return int
	 */
	public function getNextNotificationNumber(): int {
		return $this->nextNotificationNumber;
	}
	
	/**
	 * @param int $nextNotificationNumber
	 */
	public function setNextNotificationNumber(int $nextNotificationNumber): void {
		$this->nextNotificationNumber = $nextNotificationNumber;
	}
	
	/**
	 * @return bool
	 */
	public function isEnterGame(): bool {
		return $this->enterGame;
	}
	
	/**
	 * @param bool $enterGame
	 */
	public function setEnterGame(bool $enterGame): void {
		$this->enterGame = $enterGame;
	}
	
	/**
	 * @return bool
	 */
	public function isOverTime(): bool {
		return $this->overTime;
	}
	
	/**
	 * @param bool $overTime
	 */
	public function setOverTime(bool $overTime): void {
		$this->overTime = $overTime;
	}
	
	/**
	 * @return bool
	 */
	public function isMultiActive(): bool {
		return $this->multiActive;
	}
	
	/**
	 * @param bool $multiActive
	 */
	public function setMultiActive(bool $multiActive): void {
		$this->multiActive = $multiActive;
	}
	
	/**
	 * @return bool
	 */
	public function isBeginner(): bool {
		return $this->beginner;
	}
	
	/**
	 * @param bool|mixed $beginner
	 */
	public function setBeginner($beginner): void {
		$this->beginner = boolval($beginner);
	}
	
	/**
	 * @return DateTime|null
	 */
	public function getStartReflexionTime(): ?DateTime {
		return $this->startReflexionTime;
	}
	
	/**
	 * @param DateTime|null|string $startReflexionTime
	 */
	public function setStartReflexionTime($startReflexionTime): void {
		$this->startReflexionTime = $this->parseDateTime($startReflexionTime);
	}
	
	/**
	 * @return int|null
	 */
	public function getRemainingReflexionTime(): ?int {
		return $this->remainingReflexionTime;
	}
	
	/**
	 * @param int|null $remainingReflexionTime
	 */
	public function setRemainingReflexionTime(?int $remainingReflexionTime): void {
		$this->remainingReflexionTime = $remainingReflexionTime;
	}
	
	/**
	 * @return string|null
	 */
	public function getColorKey(): ?string {
		return $this->color ? (self::COLOR_MAP[$this->color] ?? null) : null;
	}
	
	/**
	 * @return int
	 */
	public function getIncome(): int {
		return $this->income;
	}
	
	/**
	 * @param int $income
	 */
	public function setIncome(int $income): void {
		$this->income = $income;
	}
	
	/**
	 * @param int $add
	 */
	public function addIncome(int $add): void {
		$this->income += $add;
	}
	
	public function applyPendingIncome(): void {
		$this->addIncome($this->getPendingIncome());
		$this->setPendingIncome(null);
	}
	
	public function canPay(int $amount): bool {
		return $amount <= $this->balance;
	}
	
	public function pay(int $amount): bool {
		if( !$this->canPay($amount) ) {
			return false;
		}
		$this->balance -= $amount;
		
		return true;
	}
	
	/**
	 * @return int
	 */
	public function getBalance(): ?int {
		return $this->balance;
	}
	
	/**
	 * @param int|null $balance
	 */
	public function setBalance(?int $balance): void {
		$this->balance = $balance;
	}
	
	/**
	 * @return int|null
	 */
	public function getPendingIncome(): ?int {
		return $this->pendingIncome;
	}
	
	/**
	 * @param int|null $pendingIncome
	 */
	public function setPendingIncome(?int $pendingIncome): void {
		$this->pendingIncome = $pendingIncome;
	}
	
	/**
	 * @return array
	 */
	public function getTurnFlags(): array {
		return $this->turnFlags;
	}
	
	/**
	 * @param array|string|null $flags
	 */
	public function setTurnFlags($flags): self {
		$this->turnFlags = $this->parseArray($flags);
		
		return $this;
	}
	
	public function getNextActionFlag(): ?string {
		if( !is_array($this->turnFlags) ) {
			return null;
		}
		for( $i = 0; $i < 3; $i++ ) {
			// 3 actions (0, 1, 2) and we end the turn
			$action = sprintf('action_%d', $i);
			if( !in_array($action, $this->turnFlags) ) {
				return $action;
			}
		}
		
		return null;
	}
	
	public function addTurnFlag(string $flag): self {
		$this->turnFlags[] = $flag;
		
		return $this;
	}
	
	public function hasTurnFlag(string $flag): bool {
		return in_array($flag, $this->turnFlags);
	}
	
	/**
	 * @return array
	 */
	public function getTurnData(): array {
		return $this->turnData;
	}
	
	/**
	 * @param array|string|null $data
	 */
	public function setTurnData($data): self {
		$this->turnData = $this->parseArray($data);
		
		return $this;
	}
	
	/**
	 * @param PigeonCard $card
	 * @return Player
	 */
	public function excludePigeonCardThisTurn(PigeonCard $card): self {
		if( !isset($this->turnData['excludedPigeonCards']) ) {
			$this->turnData['excludedPigeonCards'] = [];
		}
		$this->turnData['excludedPigeonCards'][$card->getId()] = true;
		
		return $this;
	}
	
	/**
	 * @param PigeonCard $card
	 * @return bool
	 */
	public function isPigeonCardExcludedThisTurn(PigeonCard $card): bool {
		return $this->turnData['excludedPigeonCards'][$card->getId()] ?? false;
	}
	
	/**
	 * @param string $key
	 * @return Player
	 */
	public function removeTurnInfo(string $key): self {
		unset($this->turnData[$key]);
		
		return $this;
	}
	
	/**
	 * @param string $key
	 * @param mixed $value Serializable data
	 * @return Player
	 */
	public function setTurnInfo(string $key, $value): self {
		$this->turnData[$key] = $value;
		
		return $this;
	}
	
	/**
	 * @param string $key
	 * @return mixed
	 */
	public function getTurnInfo(string $key) {
		return $this->turnData[$key] ?? null;
	}
	
	/**
	 * @return array
	 */
	public function getActionFlags(): array {
		return $this->actionFlags;
	}
	
	/**
	 * @param array|string|null $flags
	 */
	public function setActionFlags($flags): self {
		$this->actionFlags = $this->parseArray($flags);
		
		return $this;
	}
	
	public function addActionFlag(string $flag): self {
		$this->actionFlags[] = $flag;
		
		return $this;
	}
	
	public function removeActionFlag(string $flag): bool {
		$index = array_search($flag, $this->actionFlags, true);
		if( $index === false ) {
			return false;
		}
		unset($this->actionFlags[$index]);
		// Reset indexes
		$this->actionFlags = array_values($this->actionFlags);
		
		return true;
	}
	
	public function hasActionFlag(string $flag): bool {
		return in_array($flag, $this->actionFlags);
	}
	
	/**
	 * @return array
	 */
	public function getMajority(): array {
		return $this->majority;
	}
	
	/**
	 * @param array $majority
	 * @return Player
	 */
	public function setMajority($majority): Player {
		$this->majority = $this->parseArray($majority);
		
		return $this;
	}
	
	/**
	 * @return DateTime|null
	 */
	public function getMajorityUpdateDate(): ?DateTime {
		return $this->majorityUpdateDate;
	}
	
	/**
	 * @param DateTime|null|string $date
	 * @return Player
	 */
	public function setMajorityUpdateDate($date): Player {
		$this->majorityUpdateDate = $this->parseDateTime($date);;
		
		return $this;
	}
	
}
