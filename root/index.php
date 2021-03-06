<?php
	session_start();
?>
<!DOCTYPE html>
<html lang="en" >

<head>
  	<meta charset="UTF-8">
 	<title>Cool site</title>
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
	<script src="js/slider.js"></script>
	<link rel="stylesheet" type="text/css" href="css/style.css">
</head>

<body>
	<table>
		<tr>
			<td width = "25%" valign="top">
				<form id="" class = "upload_form" action="" enctype="multipart/form-data" method="POST">
						<div id ="form_div"> </div>
						<div class="2_btn_form">
							<button class="form_btn" type="button" onclick="generateForm()">Add more <b> &#43;</b> </button>
							<button id="upload_btn" class="form_btn" type="submit">Upload <b> &#8593; </b> </button>
						</div>
				</form>		
				<script  src="js/index.js"></script>


				<?php
				// Проверим, успешно ли загружен файл
				//echo basename($_FILES['uploadfile']['tmp_name']);
				if (!empty($_FILES)){
	
					$uploaddir = '../Audios/Archive/'.session_id().'/';
					if (!is_dir($uploaddir)) mkdir($uploaddir);
					for($i=0;$i<count($_FILES['uploadfile']['name']);$i++) {
						if(!is_uploaded_file($_FILES['uploadfile']['tmp_name'][$i])) {
							echo "<script> alert('Upload error!'); </script> ";
							break;
							//exit;
						}

						// Каталог, в который мы будем принимать файл:
						//$uploaddir = '../Audios/Archive/Sounds/';
						$uploadfile_name = './'.session_id().'/'.$_FILES['uploadfile']['name'][$i];
						$uploadfile = $uploaddir.basename($_FILES['uploadfile']['name'][$i]); //разобраться с русскими буквами!
		
						//проверим на допустимость расширения файла, mime-типа и размера
						//$blacklist = array(".php", ".phtml", ".php3", ".php4", ".html", ".htm");
						$whitelist = array(".wav");
						$flag = 0;
						foreach ($whitelist as $item)
							if( !preg_match("/$item\$/i", $uploadfile)) {
								echo "<script> alert('File type forbidden!!'); </script> ";
								$flag=1;
							}
						if($flag == 1)
								break;
			
						$type = $_FILES['uploadfile']['type'];
						$size = $_FILES['uploadfile']['size'];
						/*
						if (($type &#8733;!= "audio/mpeg") && ($type != "audio/wav")) {
							echo "Unsupported file";
							exit;
						}
						*/
						//if ($size > 102400) exit; //размер не более 100кб

						// Копируем файл из каталога для временного хранения файлов:
						if (copy($_FILES['uploadfile']['tmp_name'][$i], $uploadfile)) {
							//echo "<h3>Upload success!!!</h3>";
							$upload_files[$i] = $uploadfile_name;

						} else {
							$errors = error_get_last();
							echo "<h3>Upload error (2)!</h3> Error type: ".$errors['type']. ". Message: " .$errors['message'];
							exit;
						}
		
					}
				}

				?>
			</td>
		
			<td valign="top">
				<form action="" enctype="multipart/form-data" method="POST">
					<?php
						$current_dir = '../Audios/Archive/'.session_id().'/';
						function remove_ext($file){
							return explode('.', $file)[0];
						}
	
						if (is_dir($current_dir)) {
							$dir = opendir($current_dir);
							while ($file = readdir($dir)) {
								//echo 'btn_del_'.$file.'<br>';
								if($file != '.' && $file  != '..' ) {
									if(isset($_POST['btn_info_'.remove_ext($file)])){
										//print($file);
										$info = sounds_info(session_id().'/'.$file);
										if($info[1] == 1)
											$ch = "mono";
										else
											$ch = "stereo";
										$properties = array(
											"size" => ((string)$info[0])." bytes",
											"channels" =>  $ch,
											"sample_rate" => ((string)$info[2]),
											"bit_depth" => (string)$info[3],
											"sample_amt" =>(string)$info[4],
											"length" => ((string)round($info[5], 2))." sec",
										);
										//print($properties[size]);
										popup($properties);
									}			
								}
							}		
							closedir($dir);
						}

						function popup($properties){
		
							echo '<div style="position: absolute; z-index:9999;">

				
									<div id="tr_popup1" class="tr_overlay">
									<div class="tr_popup">
									<h2 id="tr_statsHeading">Info</h2>
									<button class="tr_close" href="#">&times;</button>
									<div class="tr_content">
											<div id="tr_tableStats-container" style="position: absolute; margin: 0 0 0 10px;">
												<table id="tr_tableStats" style="margin: 20px;">
													<tr>
														<td> File size: </td> <td> '.$properties["size"].' </td>
													</tr>
													<tr>
														<td> Mono/stereo: </td> <td> '.$properties["channels"].' </td>
													</tr>
													<tr>
														<td> Sample rate: </td> <td> '.$properties["sample_rate"].' </td>
													</tr>
													<tr>
														<td> Bit depth: </td> <td> '.$properties["bit_depth"].' </td>
													</tr>
													<tr>
														<td> Samples amount: </td> <td> '.$properties["sample_amt"].' </td>
													</tr>
													<tr>
														<td> Length: </td> <td> '.$properties["length"].' </td>
													</tr>
								
								
												</table>	
											</div>
					
									</div>
									</div>
									</div>
									</div>';
		
						}

						if(isset($_POST['btn_vol'])){
							increase_volume();	
						}

						function increase_volume(){
		
							$file = $_POST['file_radio'];
							$filename = strtok($file, '.');
							$extension = strtok('.');
							$k = ((double)$_POST['vol_mult']);
		
							$k = pow(10, $k/20);
							//echo $_POST['vol_mult'];

							sounds_volume(session_id().'/'.$file, session_id().'/'.$filename.'_louder.'.$extension, $k);
		
						}

						if(isset($_POST['btn_spd'])){
							increase_speed();
						}

						function increase_speed(){
							$file = $_POST['file_radio'];
							$filename = strtok($file, '.');
							$extension = strtok('.');
							$mult = (double)$_POST['text_spd'];
							sounds_speed(session_id().'/'.$file, session_id().'/'.$filename.'_faster.'.$extension, $mult);
						}

						if(isset($_POST['btn_cut'])){
							crop();
						}

						function crop(){
							$file = $_POST['file_radio'];
							$filename = remove_ext($file);
							$extension = 'wav';
							$l_border = (int)$_POST['text_cut_left'];
							$r_border = (int)$_POST['text_cut_right'];
							sounds_crop(session_id().'/'.$file, session_id().'/'.$filename.'_cropped.'.$extension, $l_border, $r_border);
						}

						if(isset($_POST['btn_rename'])){
							rename_file();
						}

						function rename_file(){
							$file = $_POST['file_radio'];
							$uploaddir = '../Audios/Archive/'.session_id().'/';
							//$filename = substr(strrchr($file, "/"), 1);
							$newname = $uploaddir .$_POST['text_rename'].'.wav';
							rename($uploaddir .$file, $newname);	
						}

						if(isset($_POST['btn_merge'])){
								merge_files();
						}
	
						function merge_files(){
								$file1 = $_POST['1_file_4_merge'];
								$file2 = $_POST['2_file_4_merge'];
								$uploaddir = session_id().'/';
								$newname = $uploaddir.'merged_'.remove_ext($file1).'_'.remove_ext($file2).'.wav';
								sounds_merge(session_id().'/'.$file1, session_id().'/'.$file2, $newname);
						}
	
						function delete_file($file){
							//$file = $_POST['file_radio'];
							//print($file);
							$uploaddir = '../Audios/Archive/'.session_id().'/';
							unlink($uploaddir.$file);//or die("Error while deleting");
						}
					?>
					<?php
	
						//$current_dir = '../Audios/Archive/'.session_id().'/';

						//echo "<p>Каталог загрузки: $current_dir</p>";
						/*if(isset($_POST['btn_del_changed_bayan.wav'])){
							print("KEK");
							delete_file();
						}*/
	
						if (is_dir($current_dir)) {
							$dir = opendir($current_dir);
							echo "<table>";
							while ($file = readdir($dir)) {
								//echo 'btn_del_'.$file.'<br>';
								if($file != '.' && $file  != '..' ) {
									if(isset($_POST['btn_del_'.remove_ext($file)])){
										//print("KEK");
										delete_file($file);
									}			
								}
							}		
							closedir($dir);
						}
					?>
	
					<table>
						<?php
							if (is_dir($current_dir)) {
								$dir = opendir($current_dir);
	
	
								while ($file = readdir($dir)) {
									if($file != '.' && $file  != '..' ) {
			
										echo "<tr class = 'files_tr'>";
										$file_with_path = session_id().'/'.$file;
										echo "<td class = 'files_td1'> <input type='radio' id='$file' name='file_radio' value=$file>$file</td>"; //Radio
										//echo "<td width = '300px'> <input type='radio' id='$file' name='file_radio' value=$file> <a title='Click to download'  href='download.php?file=$file'> $file </a>  </td>";  //Radio
			
										echo "<td class = 'files_td'> <a class = 'link2button' title='Click to download' href='Audios/Archive/$file_with_path'> <b>  &#8595; </b> </a> </td>";
										//echo "<td> <a class = 'link2button' title='Click to download' href='download.php?file=$file'> <b>  &#8595; </b> </a> </td>"; //Download
			
										echo "<td class = 'files_td'> <button title='Info' name = 'btn_info_".remove_ext($file)."' class = 'list_btn'> <b> &#63; </b> </button> </td>"; //Info
										//echo "<td> <a class = 'link2button' title='Info' href='#tr_popup1'> <b>  &#63; </b> </a> </td>"; //Info2.0
			
			
										echo "<td class = 'files_td'> <button class = 'list_btn' name = 'btn_del_".remove_ext($file)."' title='Delete file'>  &#10006; </button>  </td>"; //Delete
			
										echo "</tr>";
										//echo htmlspecialchars('download.php?file=$file');
			
										//echo "<td> <a href='/proj/sounds/Audios/Archive/".session_id()."/bayan.wav'>Download</a> </td>";

									}
								}
								closedir($dir);
							}
						?>
					</table>
	
					<p>
						<button id="btn_vol" class="page_btn" name="btn_vol">Increase volume &#9836;</button>

						<input class="slider" type="range" id="vol_slider" value="0" min="-20" max="20" step="0.2">
						<span id="vol_slider_out" > </span> dB
						<input type="hidden" id="vol_hidden" name = "vol_mult" >
    
						<script>
							var vol_slider = document.getElementById("vol_slider");
							var vol_out = document.getElementById("vol_slider_out");
							var hidden_out = document.getElementById("vol_hidden");
							vol_out.innerHTML = vol_slider.value;
							hidden_out.value = vol_slider.value;

							vol_slider.oninput = function() {
								vol_out.innerHTML = this.value;
								hidden_out.value = vol_slider.value;
							}		 

						</script>	
	
					</p>
	
					<p>
						<button id="btn_spd" class="page_btn" name="btn_spd">Increase speed &#8623;</button>

						<input type="text" class="text_inputs" name="text_spd" id="text_spd" pattern="-?\d+(\.\d{1,})?" title = "Increase by ... times" size ="3">
					</p>
	
					<p>
						<button id="btn_cut" class="page_btn" name="btn_cut">Crop &#9988;</button>

						<input type="text" class="text_inputs" name="text_cut_left" id="text_cut_left" pattern = "^[ 0-9]+$" title = "Left border (in ms), 0 for minimum border" size ="10">
						<input type="text" class="text_inputs" name="text_cut_right" id="text_cut_right" pattern = "^[ 0-9]+$" title = "Right border (in ms), -1 for maximum border" size ="10"> (bounds in ms)

					</p>	
	
					<p>
						<button id="btn_rename" class="page_btn" name="btn_rename">Rename &#9997;</button>
						<input type="text" class="text_inputs" name="text_rename" id="text_rename" title = "Input your new filename" size ="10" >.wav	
					</p>
	
					<p>
						<button id="btn_merge" class="page_btn" name="btn_merge"> Merge &harr;</button>
	
						<select class="merge_select" name="1_file_4_merge">
							<?php 
							if (is_dir($current_dir)) {
								$dir = opendir($current_dir);
								while ($file = readdir($dir)) {
									if($file != '.' && $file  != '..' ) {
										echo "<option> $file </option>";
									}
								}
								closedir($dir);
							}
							?>
						</select>
	
						<b> &amp; </b>
	
						<select class="merge_select" name="2_file_4_merge">
						<?php 
							if (is_dir($current_dir)) {
								$dir = opendir($current_dir);
								while ($file = readdir($dir)) {
									if($file != '.' && $file  != '..' ) {
										echo "<option> ".$file." </option>";
									}
								}
								closedir($dir);
							}
						?>
						</select>
	
					</p>
					
					<p>
						<button  class="page_btn" id="btn_clsfy" name="btn_clsfy">Classify &#8733;</button>
	
						<div id="clsfy"></div>
					</p>
					
					<script src='js/upload.js'></script>
					<?php
						function classify(){
							$path = session_id().'/'.$_POST['file_radio'];
							$probs = sounds_classify($path);
							return $probs;
						}
						if(isset($_POST['btn_clsfy'])){
		
							$probs = classify();
							if($probs[0]==-1) {
								echo "classify error!";
							} else {
								$instruments = array('accordion', 'piano', 'violin', 'guitar', 'flute');
								for($i=0; $i<5; $i++){
									$probs[$i] = round($probs[$i], 1);
									echo "<script>GenerateDiv('$instruments[$i]', '$probs[$i]');</script>";
								}
							}
		

							/*$instruments = array('accordion', 'piano', 'violin', 'guitar', 'flute');
							for($i=0; $i<5; $i++){
								echo "<script>GenerateDiv('$instruments[$i]', 'vvv');</script>";
							}*/
		
						} 
					?>


				</form>
			</td>
			<td id="right_side" style="vertical-align: top; width: 18%">
				<div style="border: 2px solid white; padding: 10px; background-color: white; border-radius : 10px;">
				Welcome to Cool site!

				Here you can:
				<ul>
					<li>Test the neural network for recognition of musical instruments</li>
					<li>Transform your audio:</li>
					<ol style="padding-left: 20px">
						<li>Trim you audio</li>
						<li>Increase it's volume</li>
						<li>Speed it up or slow it down or even reverse it</li>
						<li>Merge several audios</li>
					</ol>
					<li>Play it in your browser</li>
					<li>Download it back</li>
				</ul>
				
				
				
				Details:
				<ul>
					<li>For volume control, changing it by ~ +6 dB will make the amplitide twice as big.</li>
					<li>For speed control, 1 - leaves audio unchanged, values &gt 1 will make it faster, values between 0 and 1 will make it slower, and negative values will reverse the audio.</li>
					<li>To crop an audio, enter the bounds in milliseconds</li>
				</ul>	
			</div>		

			</td>
		</tr>

	</table>

</body>
</html>



	

