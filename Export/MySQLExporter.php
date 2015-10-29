<?php 

class MySQLExporter
{
  /**
   * Создает дамп таблиц БД по проекту
   **/
	   
	public function GetTables() {
		
		$tables = array(
			"carry_seances"
			
		);
		return $tables;
	}
	
	//--------------------------------------------------------------------
	
	public function GetDump($tables) {
	set_time_limit(0);
		global $DB;
		if(is_array($tables)) {
		$timecode = time();
				$fp = fopen($_SERVER["DOCUMENT_ROOT"]."/dumps/".$timecode."_dump.sql","w");		
			$text = "--
	--
	-- Дамп базы дынных за `".date("d.m.Y H:i:s")."`
	--
	-- ---------------------------------------------------
	-- ---------------------------------------------------
	";
			fwrite($fp,$text);
			
			foreach($tables as $item) {
				
					$text = "
	-- 
	-- Структура таблицы - ".$item."
	--
	";
			fwrite($fp,$text);
				
				
				$text = "";
				$text .= "DROP TABLE IF EXISTS `".$item."`;";
				
				$sql = "SHOW CREATE TABLE `".$item."`";
				$result = mysql_query($sql);//
				//$result = $DB->Query($sql, false, __LINE__);
				$row = mysql_fetch_assoc($result);

				//$row = $result->GetNext();
				
				$text .= "\n".$row['Create Table'].";";
				fwrite($fp,$text);
				
				$text = "";
				$text .=
				"
	--			
	-- Dump BD - tables : ".$item."
	--
				";
		
				$text .= "\nINSERT INTO `".$item."` VALUES";
				fwrite($fp,$text);
				
				$sql2 = "SELECT * FROM `".$item."`";
				$result2 = mysql_query($sql2);
			
				$text = "";
				for($i = 0; $i < mysql_num_rows($result2); $i++) {
				$row = mysql_fetch_row($result2);
					
					if($i == 0) $text .= "(";
						else  $text .= ",(";
					
					foreach($row as $v) {
						$text .= "\"".mysql_real_escape_string($v)."\",";
					}
					$text = rtrim($text,",");	
					$text .= ")";
					
					if($i > FOR_WRITE) {
						fwrite($fp,$text);
						$text = "";
					}
					
				}
				$text .= ";\n";
				fwrite($fp,$text);
				
				
			}
		}
		fclose($fp);
		echo "Файл дампа БД создан: /dumps/".$timecode."_dump.sql";
	}
	
    //--------------------------------------------------------------------
	

  
}

?>