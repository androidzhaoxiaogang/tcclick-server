<?php

class Model{
	/**
	 * query unique id of the device model in database, create one if not exist
	 * @param string $brand
	 * @param string $model
	 */
	public static function idFor($brand, $model){
		// try to load cached model id from memcache
		static $cached_models = null;
		if(!$cached_models){
			$cache_key = "tcclick_cached_device_models";
			$cached_models = TCClick::app()->cache->get($cache_key);
		}
		if($cached_models){
			$model_name = $brand . '::' . $model;
			if($cached_models[$model_name]) return $cached_models[$model_name];
		}
		
		$sql = 'select id from {models} where brand=:brand and model=:model';
		$row = TCClick::app()->db->query($sql, array(
				':brand' => $brand,
				':model' => $model
		))->fetch(PDO::FETCH_ASSOC);
		if(!$row){
			$sql = 'insert into {models} (brand, model) values (:brand, :model)
			on duplicate key update id=last_insert_id(id)';
			TCClick::app()->db->execute($sql, array(
					':brand' => $brand,
					':model' => $model
			));
			return TCClick::app()->db->lastInsertId();
		}else return $row['id'];
	}
	
	/**
	 * @return string
	 */
	public static function nameof($id){
	  $sql = 'select * from {models} where id=:id';
	  $row = TCClick::app()->db->query($sql, array(':id'=>$id))->fetch(PDO::FETCH_ASSOC);
	  if($row) return $row['brand'] . '::' . $row['model'];
	}
	
	public static function readableNameof($id){
		static $readable_names = array(
				"Apple::iPad1,1"=>"iPad1", "Apple::iPad1,2"=>"iPad1", "Apple::iPad1,3"=>"iPad1", "Apple::iPad1,4"=>"iPad1",
				"Apple::iPad2,1"=>"iPad2", "Apple::iPad2,2"=>"iPad2", "Apple::iPad2,3"=>"iPad2", "Apple::iPad2,4"=>"iPad2",
				"Apple::iPad2,5"=>"iPad mini", "Apple::iPad2,6"=>"iPad mini", "Apple::iPad2,7"=>"iPad mini", 
				"Apple::iPad3,1"=>"iPad3", "Apple::iPad3,2"=>"iPad3", "Apple::iPad3,3"=>"iPad3", "Apple::iPad3,4"=>"iPad3",
				"Apple::iPad3,5"=>"iPad3", "Apple::iPad3,6"=>"iPad3", "Apple::iPad3,7"=>"iPad3",
				"Apple::iPhone1,1"=>"iPhone", 
				"Apple::iPhone1,2"=>"iPhone 3G",  'Apple::iPhone1,2*'=>'iPhone 3G',
				"Apple::iPhone2,1"=>"iPhone 3GS", "Apple::iPhone2,1*"=>"iPhone 3GS",
				"Apple::iPhone3,1"=>"iPhone4",    "Apple::iPhone3,2"=>"iPhone4", "Apple::iPhone3,3"=>"iPhone4",
				"Apple::iPhone4,1"=>"iPhone4S",
				"Apple::iPhone5,1"=>"iPhone5", "Apple::iPhone5,2"=>"iPhone5",
				"Apple::iPhone5,3"=>"iPhone5c", "Apple::iPhone5,4"=>"iPhone5c",
				"Apple::iPhone6,1"=>"iPhone5s", "Apple::iPhone6,2"=>"iPhone5s",
				"Apple::iPod1,1"=>"iTouch1",
				"Apple::iPod2,1"=>"iTouch2",
				"Apple::iPod3,1"=>"iTouch3",
				"Apple::iPod4,1"=>"iTouch4",
				"Apple::iPod5,1"=>"iTouch5",      "Apple::iPod5,1*"=>"iTouch5",
				"Apple::x86_64"=>"模拟器",
		);
		$name = self::nameof($id);
		if ($readable_names[$name]) return $readable_names[$name];
		return $name;
	}
}

