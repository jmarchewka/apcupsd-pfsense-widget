<?php

/*
 * apcupsd.widget.php
 *
 * MIT License
 * 
 * Copyright (c) 2021 Joel Marchewka
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

require_once("guiconfig.inc");

if ($_REQUEST['ajax']) { 
    $results = shell_exec("apcaccess");
    if($results !== null) {
			$results_for_json=[];
			foreach(explode("\n",$results) as $i=>$v) {
				$results_for_json[trim(explode(': ',$v)[0])]=trim(explode(': ',$v)[1]);
			}
			$json_results=json_encode($results_for_json);
			$config['widgets']['apcupsd_apcaccess'] = $josn_results;
			write_config("Save apcupsd apcaccess results");
			echo $json_results;
    } else {
        echo json_encode(null);
    }
} else {
    $results = isset($config['widgets']['apcupsd_apcaccess']) ? $config['widgets']['apcupsd_apcaccess'] : null;
    if(($results !== null) && (!is_object(json_decode($results)))) {
        $results = null;
    }

?>
<table class="table table-hover table-striped table-condensed">
	<tbody>
		<tr>
			<td>Status</td><td><span id="apcupsd_apcaccess_status"></span></td>
		</tr>
		<tr>
			<td>Line Voltage</td><td><span id="apcupsd_apcaccess_line_v"></span></td>
		</tr>
		<tr>
			<td>Load</td><td>
				<div class="progress">
					<div id="apcupsd_load_meter" class="progress-bar progress-bar-striped progress-bar-success" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%">
					</div>
				</div>
				<span id="apcupsd_load_val"></span>
			</td>
		</tr>
		<tr>
			<td>Battery Charge</td><td>
				<div class="progress">
					<div id="apcupsd_bcharge_meter" class="progress-bar progress-bar-striped progress-bar-success" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%">
					</div>
				</div>
				<span id="apcupsd_bcharge_val"></span>
			</td>
		</tr>
		<tr>
			<td>Time Remaining</td><td><span id="apcupsd_apcaccess_time_remaining"></span></td>
		</tr>
		<tr>
			<td>Battery Age</td><td><span id="apcupsd_apcaccess_bat_age"></span></td>
		</tr>
	</tbody>
</table>
<a id="apcupsd_apcaccess_refresh" href="#" class="fa fa-refresh" style="display: none;"></a>
<script type="text/javascript">
function update_apcupsd_result(results) {
    if(results != null) {
			if (results.STATUS!=undefined) {
				var apcupsd_a_stat_var='<i class="fa fa-plug"/>';
				$('#apcupsd_apcaccess_status').css('color','green');
				if (results.STATUS.includes('ONBATT')) {
					apcupsd_a_stat_var='<i class="fa fa-car-battery"/>';
					$('#apcupsd_apcaccess_status').css('color','red');
				} else if (results.STATUS.includes('CHARGING')) {
					apcupsd_a_stat_var='<i class="fa fa-charging-station"/>';
					$('#apcupsd_apcaccess_status').css('color','orange');
				}
				$('#apcupsd_apcaccess_status').html(apcupsd_a_stat_var+"&nbsp;"+results.STATUS);
			}
			if (results.LINEV!=undefined) {
				$('#apcupsd_apcaccess_line_v').html('<i class="fa fa-bolt"/>&nbsp;'+results.LINEV);
			}
			if (results.LOADPCT!=undefined) {
				setProgress('apcupsd_load_meter', parseInt(results.LOADPCT));
				$('#apcupsd_load_val').html('<i class="fa fa-battery-full"/>&nbsp;'+results.LOADPCT);
			}
			if (results.BCHARGE!=undefined) {
				setProgress('apcupsd_bcharge_meter', parseInt(results.BCHARGE));
				$('#apcupsd_bcharge_val').html('<i class="fa fa-battery-full"/>&nbsp;'+results.BCHARGE);
			}
			if (results.TIMELEFT!=undefined) {
				$('#apcupsd_apcaccess_time_remaining').html('<i class="fa fa-clock-o"/>&nbsp;'+results.TIMELEFT);
			}
			if (results.BATTDATE!=undefined) {
				var apcupsd_battdate_date = new Date(results.BATTDATE);
				var apcupsd_nowdate_date = Date.now();
				var apcupsd_battdays=Math.floor((apcupsd_nowdate_date-apcupsd_battdate_date.getTime()) / (1000 * 3600 * 24));
				var apcupsd_battdays_icon='<i class="fa fa-calendar-check-o"/>';
				$('#apcupsd_apcaccess_bat_age').css('color','green');
				if (apcupsd_battdays>730) {
					apcupsd_battdays_icon='<i class="fa fa-calendar-times-o"/>';
					$('#apcupsd_apcaccess_bat_age').css('color','orange');
				} else if(apcupsd_battdays>365) {
					apcupsd_battdays_icon='<i class="fa fa-calendar-minus-o"/>';
					$('#apcupsd_apcaccess_bat_age').css('color','red');
			}
				
				$('#apcupsd_apcaccess_bat_age').html(apcupsd_battdays_icon+'&nbsp;'+apcupsd_battdays.toString()+" days");
			}
		}
}

function update_apcupsd_apcaccess() {
    $.ajax({
        type: 'POST',
        url: "/widgets/widgets/apcupsd.widget.php",
        dataType: 'json',
        data: {
            ajax: "ajax"
        },
        success: function(data) {
            update_apcupsd_result(data);
        },
        error: function() {
            update_apcupsd_result(null);
        },
        complete: function() {
            $('#apcupsd_apcaccess_refresh').off("click").removeClass("fa-spin").click(function() {
                update_apcupsd_apcaccess();
                return false;
            });
        }
    });
}
events.push(function() {

  update_apcupsd_apcaccess();

	function apcupsd_refresh_callback(s) {
		update_apcupsd_apcaccess();
	}

	var postdata = {
		ajax: "ajax",
	 };

	var refreshObject = new Object();
	refreshObject.name = "RefreshAPCUPSD";
	refreshObject.url = "/widgets/widgets/apcupsd.widget.php";
	refreshObject.callback = apcupsd_refresh_callback;
	refreshObject.parms = postdata;
	refreshObject.freq = 1;

	register_ajax(refreshObject);

});

</script>
<?php } ?>
