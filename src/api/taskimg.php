<?php

/*
 * Draws a graphic about chunk progress
 */

use DBA\Chunk;
use DBA\OrderFilter;
use DBA\QueryFilter;
use DBA\Task;

require_once(dirname(__FILE__) . "/../inc/load.php");

/** @var Login $LOGIN */

//check if there is a session
if (!$LOGIN->isLoggedin()) {
  die("No access!");
}

//get image dimensions
$size = array(intval($_GET["x"]), intval($_GET["y"]));
if ($size[0] == 0 || $size[0] == 0) {
  die("INV size!");
}

//check if task exists and get information
if (isset($_GET['task'])) {
  $task = $FACTORIES::getTaskFactory()->get($_GET['task']);
  if ($task == null) {
    die("Not a valid task!");
  }
  $taskWrapper = $FACTORIES::getTaskWrapperFactory()->get($task->getTaskWrapperId());
  if ($taskWrapper == null) {
    die("Inconsistency on task!");
  }
}
else if (isset($_GET['supertask'])) {
  $taskWrapper = $FACTORIES::getTaskWrapperFactory()->get($_GET['supertask']);
  if ($taskWrapper == null) {
    die("Invalid task wrapper!");
  }
}

//create image
$image = imagecreatetruecolor($size[0], $size[1]);
imagesavealpha($image, true);

//set colors
$transparency = imagecolorallocatealpha($image, 0, 0, 0, 127);
$yellow = imagecolorallocate($image, 255, 255, 0);
$red = imagecolorallocate($image, 255, 0, 0);
$grey = imagecolorallocate($image, 192, 192, 192);
$green = imagecolorallocate($image, 0, 255, 0);
$blue = imagecolorallocate($image, 60, 60, 245);

//prepare image
imagefill($image, 0, 0, $transparency);

if ($taskWrapper->getTaskType() == DTaskTypes::SUPERTASK && isset($_GET['supertask'])) {
  // handle supertask progress drawing here
  $qF = new QueryFilter(Task::TASK_WRAPPER_ID, $taskWrapper->getId(), "=");
  $oF = new OrderFilter(Task::PRIORITY, "DESC");
  $tasks = $FACTORIES::getTaskFactory()->filter(array($FACTORIES::FILTER => $qF, $FACTORIES::ORDER => $oF));
  $numTasks = sizeof($tasks);
  for ($i = 0; $i < sizeof($tasks); $i++) {
    $qF = new QueryFilter(Chunk::TASK_ID, $tasks[$i]->getId(), "=");
    $chunks = $FACTORIES::getChunkFactory()->filter(array($FACTORIES::FILTER => $qF));
    $progress = 0;
    foreach ($chunks as $chunk) {
      $progress += $chunk->getCheckpoint();
    }
    $qF = new QueryFilter(Chunk::TASK_ID, $tasks[$i]->getId(), "=");
    $chunks = $FACTORIES::getChunkFactory()->filter(array($FACTORIES::FILTER => $qF));
    $cracked = 0;
    foreach ($chunks as $chunk) {
      $cracked += $chunk->getCracked();
    }
    if ($cracked > 0) {
      imagefilledrectangle($image, $i * $size[0] / $numTasks, 0, ($i + 1) * $size[0] / $numTasks, $size[1] - 1, $green);
    }
    else if ($tasks[$i]->getKeyspace() > 0 && $progress >= $tasks[$i]->getKeyspace()) {
      imagefilledrectangle($image, $i * $size[0] / $numTasks, 0, ($i + 1) * $size[0] / $numTasks, $size[1] - 1, $blue);
    }
    else if ($tasks[$i]->getKeyspace() > 0 && $progress > 0) {
      imagefilledrectangle($image, $i * $size[0] / $numTasks, 0, ($i + 1) * $size[0] / $numTasks, $size[1] - 1, $yellow);
    }
    else {
      imagefilledrectangle($image, $i * $size[0] / $numTasks, 0, ($i + 1) * $size[0] / $numTasks, $size[1] - 1, $grey);
    }
  }
}
else {
  $progress = $task->getKeyspaceProgress();
  $keyspace = max($task->getKeyspace(), 1);
  $taskId = $task->getId();
  
  //load chunks
  $qF = new QueryFilter(Task::TASK_ID, $task->getId(), "=");
  $chunks = $FACTORIES::getChunkFactory()->filter(array($FACTORIES::FILTER => $qF));
  foreach ($chunks as $chunk) {
    $start = floor(($size[0] - 1) * $chunk->getSkip() / $keyspace);
    $end = floor(($size[0] - 1) * ($chunk->getSkip() + $chunk->getLength()) / $keyspace) - 1;
    //division by 10000 is required because rprogress is saved in percents with two decimals
    $current = floor(($size[0] - 1) * ($chunk->getSkip() + $chunk->getLength() * $chunk->getProgress() / 10000) / $keyspace) - 1;
    
    if ($current > $end) {
      $current = $end;
    }
    
    if ($end - $start < 3) {
      if ($chunk->getState() >= 6) {
        imagefilledrectangle($image, $start, 0, $end, $size[1] - 1, $red);
      }
      else if ($chunk->getCracked() > 0) {
        imagefilledrectangle($image, $start, 0, $end, $size[1] - 1, $green);
      }
      else {
        imagefilledrectangle($image, $start, 0, $end, $size[1] - 1, $yellow);
      }
    }
    else {
      if ($chunk->getState() >= 6) {
        imagerectangle($image, $start, 0, $end, ($size[1] - 1), $red);
      }
      else {
        imagerectangle($image, $start, 0, $end, ($size[1] - 1), $grey);
      }
      if ($chunk->getCracked() > 0) {
        imagefilledrectangle($image, $start + 1, 1, $current - 1, $size[1] - 2, $green);
      }
      else {
        imagefilledrectangle($image, $start + 1, 1, $current - 1, $size[1] - 2, $yellow);
      }
    }
  }
}

//send image data to output
header("Content-type: image/png");
header("Cache-Control: no-cache");
imagepng($image);




