<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit(1); }
$root = dirname(__DIR__);
$dir = $root . '/writable/cache';
if (!is_dir($dir) && !@mkdir($dir, 0750, true) && !is_dir($dir)) { fwrite(STDERR, "Snapshot directory unavailable\n"); exit(0); }
function sysRead(string $p): string { $v = @file_get_contents($p); return $v === false ? '' : trim($v); }
function sysBytes(int $n): string { $u=['B','KB','MB','GB','TB']; $i=0; $v=(float)$n; while($v>=1024 && $i<4){$v/=1024;$i++;} return number_format($v,2).' '.$u[$i]; }
$os=[];
foreach(preg_split('/\r?\n/',sysRead('/etc/os-release')) as $line){if(strpos($line,'=')!==false){[$k,$v]=explode('=',$line,2);$os[$k]=trim($v," \t\n\r\0\x0B\"'");}}
$cpu=sysRead('/proc/cpuinfo'); $model='Tidak tersedia';
if(preg_match('/^(?:model name|Hardware|Processor)\s*:\s*(.+)$/mi',$cpu,$m)) {
    $candidate=trim($m[1]);
    if($candidate!=='' && !preg_match('/^\d+(?:\.\d+)?$/',$candidate)) $model=$candidate;
}
// Some virtual machines expose a numeric placeholder in cpuinfo; try lscpu as a fallback.
if($model==='Tidak tersedia' && function_exists('shell_exec')) {
    $disabled=array_filter(array_map('trim',explode(',',(string)ini_get('disable_functions'))));
    if(!in_array('shell_exec',$disabled,true)) {
        $lscpu=@shell_exec('LC_ALL=C lscpu 2>/dev/null');
        if(is_string($lscpu) && preg_match('/^(?:Model name|Model|Vendor ID):\s*(.+)$/mi',$lscpu,$m)) {
            $candidate=trim($m[1]);
            if($candidate!=='' && !preg_match('/^\d+(?:\.\d+)?$/',$candidate)) $model=$candidate;
        }
    }
}
$cores=preg_match_all('/^processor\s*:/m',$cpu);
$mem=[];
foreach(preg_split('/\r?\n/',sysRead('/proc/meminfo')) as $line){if(preg_match('/^(MemTotal|MemAvailable):\s+(\d+)/',$line,$m))$mem[$m[1]]=(int)$m[2]*1024;}
$total=$mem['MemTotal']??0; $avail=$mem['MemAvailable']??null;
$memory=['total'=>$total?sysBytes($total):'Tidak tersedia','used'=>($total&&$avail!==null)?sysBytes(max(0,$total-$avail)):'Tidak tersedia','used_percent'=>($total&&$avail!==null)?round((($total-$avail)/$total)*100,1):null];
$up=sysRead('/proc/uptime'); $uptime='Tidak tersedia';
if($up!==''){$s=(int)floor((float)strtok($up,' '));$uptime=(intdiv($s,86400)?intdiv($s,86400).' hari ':'').intdiv($s%86400,3600).' jam '.intdiv($s%3600,60).' menit';}
$panel=is_dir('/www/server/panel'); $panelVersion=$panel?'Terpasang, versi tidak dapat dibaca':'Tidak terpasang / tidak terdeteksi';
if($panel){
    // Prefer aaPanel's own public.version() helper when its bundled Python is available.
    $panelPython='/www/server/panel/pyenv/bin/python';
    if(is_executable($panelPython) && function_exists('shell_exec')) {
        $disabled=array_filter(array_map('trim',explode(',',(string)ini_get('disable_functions'))));
        if(!in_array('shell_exec',$disabled,true)) {
            $command=escapeshellarg($panelPython).' -c '.escapeshellarg("import sys; sys.path.insert(0, '/www/server/panel'); import public; print(public.version())").' 2>/dev/null';
            $detected=trim((string)@shell_exec($command));
            if(preg_match('/^[0-9]+(?:\.[0-9]+){1,3}$/',$detected)) $panelVersion=$detected;
        }
    }
    if($panelVersion==='Terpasang, versi tidak dapat dibaca') {
        foreach(['/www/server/panel/data/version.pl','/www/server/panel/data/version.txt','/www/server/panel/config/version.pl','/www/server/panel/class/panelVersion.py'] as $f){
            $v=sysRead($f); if($v==='')continue;
            if(preg_match('/(?:version|VERSION)\s*[=:\(]\s*["\']?([0-9]+(?:\.[0-9]+){1,3})/i',$v,$m)){$panelVersion=$m[1];break;}
            if(preg_match('/^[0-9]+(?:\.[0-9]+){1,3}$/',$v)){$panelVersion=$v;break;}
        }
    }
}
$data=['generated_at'=>date(DATE_ATOM),'server'=>['hostname'=>gethostname()?:'Tidak tersedia','os'=>$os['PRETTY_NAME']??'Tidak tersedia','kernel'=>php_uname('r'),'architecture'=>php_uname('m'),'cpu_model'=>$model,'cpu'=>$cores>0?$cores.' core':'Tidak tersedia','memory'=>$memory,'uptime'=>$uptime],'panel'=>['installed'=>$panel,'version'=>$panelVersion]];
$json=json_encode($data,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
if($json===false){fwrite(STDERR,"Snapshot encoding failed\n");exit(0);}
$tmp=$dir.'/system-info.json.tmp';
if(@file_put_contents($tmp,$json.PHP_EOL,LOCK_EX)===false){fwrite(STDERR,"Snapshot write skipped\n");exit(0);}
@chmod($tmp,0640);
if(!@rename($tmp,$dir.'/system-info.json')){@unlink($tmp);fwrite(STDERR,"Snapshot publish skipped\n");exit(0);}
echo "[OK] System information snapshot refreshed.\n";
