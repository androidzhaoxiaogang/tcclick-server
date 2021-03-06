<?php

class EventsController extends Controller {
  public function filters() {
    return array(
      "AdminRequiredFilter",
      "ExternalAccessFilter - index, view",
    );
  }

  public function actionIndex() {
    $this->renderCompatibleWithExternalSite('index');
  }

  public function actionView() {
    $event = Event::loadById($_GET['id']);
    if($event || isset($_GET['external_site_id'])) {
      $this->renderCompatibleWithExternalSite('view', array('event' => $event));
    } else {
      $this->redirect(TCClick::app()->root_url . 'events');
    }
  }

  public function actionAjaxDailyCounts() {
    header("Content-type: application/json;charset=utf-8");
    $today = date("Y-m-d");
    if(!empty($_GET['from'])) {
      $start_date = date('Y-m-d', strtotime($_GET['from']));
    } else
      $start_date = date("Y-m-d", time() - 86400 * 30);
    $end_date = $today;
    $version_id = intval($_GET['version_id']);
    $event_id = intval($_GET['event_id']);
    $param_id = intval($_GET['param_id']);
    if(!$param_id) {
      $sql = "select * from {event_params} where event_id ={$event_id} limit 1";
      $row = TCClick::app()->db->query($sql)->fetch(PDO::FETCH_ASSOC);
      if(!empty($row)) {
        $param_id = $row['param_id'];
      }
    }

    $date = self::datesArrayForJsonOutput($start_date, $end_date);
    $json = array("stats" => array(), "dates" => $date, "result" => "success");
    $daily_count_with_dates = array();

    $sql = "select * from {counter_daily_events} where event_id={$event_id}
				and param_id={$param_id} and date>='$start_date' and date<='{$today}'";
    if($version_id) {
      $sql .= " and version_id={$version_id}";
    }

    $stmt = TCClick::app()->db->query($sql);
    foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
      if(!$daily_count_with_dates[$row['value_id']]) {//初始化
        $daily_count_with_dates[$row['value_id']] = self::generateZeroDailyCount($start_date, $end_date);
      }
      $daily_count_with_dates[$row['value_id']][$row['date']] += intval($row['count']);
    }

    $all_count = array();
    foreach($daily_count_with_dates as $value_id => $count_data) {
      foreach($count_data as $date => $count) {
        $all_count[$date] += $count;
      }
    }

    foreach($daily_count_with_dates as $value_id => $count_data) {
      $daily_count = array();
      foreach($count_data as $date => $count) {
        if($all_count[$date]) $daily_count[] = round($count / $all_count[$date], 5);
        else $daily_count[] = 0;
      }
      $json['stats'][] = array("data" => $daily_count, "name" => EventName::nameOf($value_id));
    }
    echo json_encode($json);
  }

  public function actionAjaxDailyCountsSpline() {
    header("Content-type: application/json;charset=utf-8");
    $today = date("Y-m-d");
    if(!empty($_GET['from'])) {
      $start_date = date('Y-m-d', strtotime($_GET['from']));
    } else
      $start_date = date("Y-m-d", time() - 86400 * 30);
    $end_date = $today;
    $event_id = intval($_GET['event_id']);
    $version_id = intval($_GET['version_id']);

    $param_id = intval($_GET['param_id']);
    if(!$param_id) {
      $sql = "select * from {event_params} where event_id ={$event_id} limit 1";
      $row = TCClick::app()->db->query($sql)->fetch(PDO::FETCH_ASSOC);
      if(!empty($row)) {
        $param_id = $row['param_id'];
      }
    }
    $date = self::datesArrayForJsonOutput($start_date, $end_date);
    $json = array("stats" => array(), "dates" => $date, "result" => "success");
    $daily_count_with_dates = array();

    $sql = "select * from {counter_daily_events} where event_id={$event_id}
				and param_id={$param_id} and date>='$start_date' and date<='{$today}'";
    if($version_id) {
      $sql .= " and version_id={$version_id}";
    }

    $stmt = TCClick::app()->db->query($sql);
    foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
      if(!$daily_count_with_dates[$row['value_id']]) {//初始化
        $daily_count_with_dates[$row['value_id']] = self::generateZeroDailyCount($start_date, $end_date);
      }
      $daily_count_with_dates[$row['value_id']][$row['date']] += intval($row['count']);
    }

    foreach($daily_count_with_dates as $key => $count_data) {
      $daily_count = array();
      foreach($count_data as $date => $count) {
        $daily_count[] = $count;
      }
      $json['stats'][] = array("data" => $daily_count, "name" => EventName::nameOf($key));
    }
    echo json_encode($json);

  }

  public static function datesArrayForJsonOutput($start_date, $end_date) {
    $start_time = strtotime($start_date);
    $end_time = strtotime($end_date);
    $dates = array();
    for($time = $start_time; $time <= $end_time; $time += 86400) {
      $dates[] = date("m-d", $time);
    }

    return $dates;
  }

  public static function generateZeroDailyCount($start_date, $end_date) {
    $start_time = strtotime($start_date);
    $end_time = strtotime($end_date);
    $daily_count = array();
    for($time = $start_time; $time <= $end_time; $time += 86400) {
      $daily_count[date("Y-m-d", $time)] = 0;
    }

    return $daily_count;
  }

}

