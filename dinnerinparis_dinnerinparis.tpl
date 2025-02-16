{OVERALL_GAME_HEADER}

<div class="page-wrapper responsive-layer">
	<div class="system-alert" hidden>{endingGame}</div>

	<div class="container-xxl">
		<div class="game-table" id="GameTable">
			<div class="whiteblock game-box">
				<h5>{restaurantBoxTitle}</h5>
				<div class="row list-restaurant"></div>
				<div class="list-restaurant-panel"></div>
				<div id="HeadContents"></div>
			</div>
			<div class="row">
				<div class="col-12 col-xl-auto">
					<!-- debug select-card-resource select-card-objective select-card-pigeon select-card-majority -->
					<div class="game-board">
						<div class="tile-grid"></div>
						<div class="card-objective-picker">
							<div class="draw-pile card-objective-container"></div>
							<div class="card-river">
								<!-- BEGIN objectiveCardRiverSlot -->
								<div class="card-slot card-objective-container"></div>
								<!-- END objectiveCardRiverSlot -->
							</div>
						</div>
						<div class="card-pigeon-picker">
							<div class="draw-pile card-pigeon-container"></div>
						</div>
						<div class="card-majority-picker">
							<div class="card-slot card-majority-container"></div>
						</div>
						<div class="card-resource-picker">
							<div class="discard-pile card-resource-container"></div>
							<div class="draw-pile card-resource-container"></div>
							<div class="card-river">
								<div class="card-slot card-resource-container"></div>
								<div class="card-slot card-resource-container"></div>
								<div class="card-slot card-resource-container"></div>
								<div class="card-slot card-resource-container"></div>
							</div>
						</div>
					</div>
				</div>
				<div class="col-12 col-xl-4 col-xxl-5">
					<div class="game-player-board-list">
					</div>
				</div>
			</div>
		</div>

		<!--
		<div class="server-logs whiteblock">
			<h5>Setup Logs</h5>
			<pre>{LOGS}</pre>
		</div>
		-->
	</div>

	<div class="page-overlay"></div>
</div>


<script type="text/javascript">

// Javascript HTML templates

/*
// Example:
var jstpl_some_game_item='<div class="my_game_item" id="my_game_item_${MY_ITEM_ID}"></div>';

*/

</script>

{OVERALL_GAME_FOOTER}
