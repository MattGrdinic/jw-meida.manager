<?php

class MediaLogger {
	
	private $error;
	private $can_log = true;
	
	function __construct() {
		// ensure we have an accessible log file
		try {
			if(!file_exists('manage_media.log')){
				$this->can_log = false;
				throw new Exception('Log file Doesn\'t exist.');
			} else {
				// can we write to the file
				try {
					if(!is_writable('manage_media.log')){
						$this->can_log = false;
						throw new Exception('Log file cannot be written to.');
					}
				} catch(Exception $e) {
					$this->error = $e->getMessage();
				}
			}
		} catch (Exception $e) {
			$this->error = $e->getMessage();
		}
	}
	
	public function get_errors(){
		return $this->error;	
	}
	
	/**
	 * Add a verbose log message
	 *
	 * @param string $entry
	 */
	public function add_verbose($entry){
		if(LOG_LEVEL == 'VERBOSE' && $this->can_log){
			$handle = fopen('manage_media.log', 'ab');
			fwrite($handle, "\n".date(LOG_TIME_FORMAT, time()).' [ Verbose  ] ' . $entry);
			fclose($handle);
		}
	}
	
	/**
	 * Add a standard log message
	 *
	 * @param string $entry
	 */
	public function add_standard($entry){
		if(LOG_LEVEL == 'VERBOSE' || LOG_LEVEL == 'STANDARD' && $this->can_log){
			$handle = fopen('manage_media.log', 'ab');
			fwrite($handle, "\n".date(LOG_TIME_FORMAT, time()).' [ Standard ] ' . $entry);
			fclose($handle);
		}
	}
}

?>