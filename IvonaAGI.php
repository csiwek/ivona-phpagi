<?php 


class IvonaAGI extends class AGI {

    private $enableDebug =1;
    private $format = null;
    private $codec = null;
    private $samplerate = null;

    public function __construct()
    {
	$this->debug(__METHOD__." Created instance of ".__CLASS__);
    }


    private function debug($message)
    {
	if ($this->enableDebug){
		syslog(LOG_DEBUG, $message);
	}

    }



    public function say_tts($text, $lang = null, $speed = null, $noanswer=null)
    {

	$this->convertMP3ToWAV($filename);
	$this->detectFormat();
	$destFile = $this->CreateAsteriskFile($speed);
	$this->debug(__METHOD__ . " Will try to play file: $destFile");
	if($destFile) {
		$playstring = $destFile;
		if ($noanswer){
			$playstring .= ",noanswer";
		}
	}
	//$this->agi->stream_file($this->destFile);
	$this->agi->exec("Playback", $playstring);
    }

    private function convertMP3ToWAV($filename){
		file_exists($filename.".wav")){
		$this->debug(__METHOD__ . " WAV Filename already exists: ".$filename);
		return 1;
	}
	$mpg123cmd = $this->mpg123." -q -w $filename.wav $filename.mp3 ";
	$this->debug(__METHOD__ . " executing mpg123: $mpg123cmd");
	$retMpg123 = exec($mpg123cmd);
	$this->debug(__METHOD__ . " mpg123 returned $retMpg123");
    }

    private function CreateAsteriskFile($speed){
	$destFile = $this->filename . "_" . $this->samplerate."_".$speed ;
	if (file_exists($destFile . "." . $this->format)){
		$this->debug(__METHOD__ . " Asterisk sound file already exists: ".$destFile." In ".$this->samplerate."_".$speed);
		return $destFile;
	}
	$soxcmd = $this->sox ." ". $this->filename.".wav -q -r " . $this->samplerate . " -t raw ". $destFile . "." . $this->format;
	$this->debug(__METHOD__ . " executing sox: $soxcmd");
	exec($soxcmd);
	return $destFile;
    }

    private function detectFormat(){
	if (!$this->agi){
		$this->debug(__METHOD__ . " AGI not set. cannot detect the format");
	exit;
	}
	$codec = $this->agi->get_variable("CHANNEL(audionativeformat)"); $codec = $codec['data'];
	$this->debug(__METHOD__ . " Detected codec: $codec");
	$this->codec = $codec;
	if (preg_match('/(silk|sln)12/', $codec)) { $this->format = "sln12" ; $this->samplerate = 12000;}
	elseif (preg_match('/(speex|slin|silk)16|g722|siren7/', $codec)) { $this->format = "sln16" ; $this->samplerate = 16000;}
	elseif (preg_match('/(speex|slin|celt)32|siren14/', $codec)) { $this->format = "sln32"; $this->samplerate = 32000;}
	elseif (preg_match('/(celt|slin)44/', $codec)) { $this->format = "sln44"; $this->samplerate = 44100;}
	elseif (preg_match('/(celt|slin)48/', $codec)) { $this->format = "sln48"; $this->samplerate = 48000;}
	else { $this->format = "sln"; $this->samplerate = 8000;}
	$this->debug(__METHOD__ . " Samling rate for this format is : ". $this->format.":".$this->samplerate);
	return 1;
    }





}



?>
