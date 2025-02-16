This directory is meant to hold PHP / js files for inclusion in your
<gamename>.game.php / gamename.js if needed.

It is recommended to name your files starting with a trigram of your game name.
For example if you have a class to manage pathfinding in the game
Tobago, it will be called TBGPathFinding.php (and the class inside be
called TBGPathFinding) to avoid potential namespace collisions.

*Contains files shared with BGA ftp and deployed on BGA servers.*

Sowapps Note:
Please consider putting your JS file into the "js" folder as translation system won't look up inside folder "dist/".
