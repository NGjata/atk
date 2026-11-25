<?php

namespace Sintattica\Atk\Utils;

/**
 * Utility for browser detection.
 *
 * @author Wim Kosten <wim@ibuildings.nl>
 * @author Ivo Jansch <ivo@achievo.org>
 */
class BrowserInfo
{
    public $ua = '';
    public $full_version = '';
    public $browser = 'unknown';
    public $major = 0;
    public $minor = 0;
    public $os = '';
    public $platform = '';
    public $short = '';
    public $brName = '';
    public $osName = '';
    public $hasGui = 0;
    public $spider = 0;
    public $family = '';
    public $gecko = 0;
    public $osname = '';

    public function __construct($ua = '')
    {

        // set ua
        if (trim($ua) == '') {
            $this->ua = $_SERVER['HTTP_USER_AGENT'];
        } else {
            $this->ua = $ua;
        }

        // check the browser and set properties
        $this->detectAgent();

        // detect platform and OS
        $this->detectOS();

        // set the family
        if ($this->eregi('gecko', $this->ua)) {
            $this->gecko = 1;
        }

        switch (strtolower($this->browser)) {
            case 'msie':
                if ($this->major < 4) {
                    $this->family = 'ie3';
                } else {
                    if ($this->major >= 5) {
                        $this->family = 'ie5up';
                    } else {
                        $this->family = 'ie4up';
                    }
                }
                break;

            case 'opera':
                $this->family = 'opera';
                break;

            case 'netscape':
                if ($this->major < 6) {
                    $this->family = 'nn4';
                } else {
                    $this->family = 'nn6';
                }
                break;

            default:
                if ($this->gecko == 1) {
                    $this->family = 'gecko';
                } else {
                    $this->family = 'unknown';
                }
        }

        $this->short = $this->browser.' '.$this->full_version;
    }

    // actual detection
    public function detectAgent()
    {
        //MSIE
        $info = [];
        if ($this->eregi('MSIE ([0-9].[0-9a-zA-Z]{1,4})', $this->ua, $info) || $this->eregi('Microsoft Internet Explorer ([0-9].[0-9a-zA-Z]{1,4})', $this->ua, $info)) {
            $this->full_version = $info[1];
            $this->browser = 'MSIE';
            $this->brName = 'MS Internet Explorer';
            $this->hasGui = 1;

            // check for Opera faking MSIE
            if ($this->eregi('Opera ([0-9].[0-9a-zA-Z]{1,4})', $this->ua, $info) || $this->eregi('Opera/([0-9].[0-9a-zA-Z]{1,4})', $this->ua, $info)) {
                $this->full_version = $info[1];
                $this->browser = 'Opera';
                $this->brName = 'Opera';
                $this->hasGui = 1;
            }

            // check for WebTV
            if ($this->eregi('WebTV/([0-9].[0-9a-zA-Z]{1,4})', $this->ua, $info)) {
                $this->full_version = $info[1];
                $this->browser = 'WebTV';
                $this->brName = 'WebTV';
                $this->hasGui = 1;
            }

            // check for AOL
            if ($this->eregi('AOL ([0-9].[0-9a-zA-Z]{1,4})', $this->ua, $info)) {
                $ver = $this->full_version;
                $this->full_version = $info[1];
                $this->browser = 'AOL (MSIE '.$ver.')';
                $this->brName = 'AOL';
                $this->hasGui = 1;
            }
        }

        // Opera
        if ($this->eregi('Opera ([0-9].[0-9a-zA-Z]{1,4})', $this->ua, $info) || $this->eregi('Opera/([0-9].[0-9a-zA-Z]{1,4})', $this->ua, $info)) {
            $this->full_version = $info[1];
            $this->browser = 'Opera';
            $this->brName = 'Opera';
            $this->hasGui = 1;
        } // iCab
        elseif ($this->eregi('iCab ([0-9].[0-9a-zA-Z]{1,4})', $this->ua, $info) || $this->eregi('iCab/([0-9].[0-9a-zA-Z]{1,4})', $this->ua, $info)) {
            $this->full_version = $info[1];
            $this->browser = 'iCab';
            $this->brName = 'iCAB';
            $this->hasGui = 1;
        } // Lynx
        elseif ($this->eregi('Lynx ([0-9].[0-9a-zA-Z.]{1,9})', $this->ua, $info) || $this->eregi('Lynx/([0-9].[0-9a-zA-Z.]{1,9})', $this->ua, $info)) {
            $this->full_version = $info[1];
            $this->browser = 'Lynx';
            $this->platform = 'Unix';
            $this->brName = 'Lynx (textbrowser)';
            $this->hasGui = 0;
        } // Wget
        elseif ($this->eregi('Wget/([0-9].[0-9a-zA-Z.]{1,9})', $this->ua, $info)) {
            $this->full_version = $info[1];
            $this->browser = 'Wget';
            $this->platform = 'Unix';
            $this->brName = 'Wget';
            $this->hasGui = 0;
        } // Snoopy (PHP HTTP stuff)
        elseif ($this->eregi('Snoopy v([0-9].[0-9a-zA-Z.]{1,9})', $this->ua, $info)) {
            $this->full_version = $info[1];
            $this->browser = 'Snoopy';
            $this->brName = 'Snoopy';
            $this->hasGui = 0;
        } // GetRight
        elseif ($this->eregi('GetRight/([0-9].[0-9a-zA-Z.]{1,9})', $this->ua, $info)) {
            $this->full_version = $info[1];
            $this->browser = 'GetRight';
            $this->platform = 'Win9xNT';
            $this->brName = 'GetRight';
            $this->hasGui = 0;
        } // KDE Konqueror
        elseif ($this->eregi('Konqueror ([0-9].[0-9a-zA-Z]{1,4})', $this->ua, $info) || $this->eregi('Konqueror/([0-9].[0-9a-zA-Z]{1,4})', $this->ua, $info)) {
            $this->full_version = $info[1];
            $this->browser = 'Konqueror';
            $this->platform = 'Unix';
            $this->brName = 'KDE Konqueror';
            $this->hasGui = 1;
        } // Netscape 6
        elseif ($this->eregi('Netscape6/([0-9].[0-9a-zA-Z]{1,4})', $this->ua, $info)) {
            $this->full_version = $info[1];
            $this->browser = 'Netscape';
            $this->brName = 'Netscape';
            $this->hasGui = 1;
        } // NCSA Mosaic
        elseif ($this->eregi('nsca mosaic ([0-9].[0-9a-zA-Z]{1,4})', strtolower($this->ua), $info)) {
            $this->full_version = $info[1];
            $this->browser = 'NSCA Mosaic';
            $this->brName = 'NSCA Mosaic';
            $this->hasGui = 1;
        } // Prodigy Classic
        elseif ($this->eregi('prodigy-wb/([0-9].[0-9a-zA-Z]{1,4})', strtolower($this->ua), $info)) {
            $this->full_version = $info[1];
            $this->browser = 'Prodigy Classic';
            $this->brName = $this->browser;
            $this->hasGui = 1;
        } // Nokia Communicator
        elseif ($this->eregi('Nokia-Communicator-WWW-Browser ([0-9].[0-9a-zA-Z]{1,4})', strtolower($this->ua), $info)) {
            $this->full_version = $info[1];
            $this->browser = 'Nokia Communicator';
            $this->brName = $this->browser;
            $this->hasGui = 0;
        }

        //
        // CRAWLERS, SPIDERS ETC.
        //

        // Inktomi
        elseif ($this->eregi('Slurp/', $this->ua, $info)) {
            $this->full_version = 0;
            $this->browser = 'Inktomi Web Robot';
            $this->brName = 'Inktomi Web Robot';
            $this->hasGui = 0;
            $this->spider = 1;
        } // Google
        elseif ($this->eregi('Googlebot/([0-9].[0-9a-zA-Z]{1,4})', $this->ua, $info)) {
            $this->full_version = $info[1];
            $this->browser = 'Google WebCrawler';
            $this->brName = 'Google WebCrawler';
            $this->hasGui = 0;
            $this->spider = 1;
        } // ASPSeek
        elseif ($this->eregi('ASPSeek/([0-9].[0-9a-zA-Z]{1,4})', $this->ua, $info)) {
            $this->full_version = $info[1];
            $this->browser = 'ASPSeek';
            $this->brName = 'ASPSeek';
            $this->hasGui = 0;
            $this->spider = 1;
        } // ArchitextSpider
        elseif ($this->eregi('ArchitextSpider', $this->ua, $info)) {
            $this->full_version = 0;
            $this->browser = 'ArchitextSpider';
            $this->brName = 'ArchitextSpider';
            $this->hasGui = 0;
            $this->spider = 1;
        } // Lycos spider
        elseif ($this->eregi('Lycos_Spider', $this->ua, $info)) {
            $this->full_version = 0;
            $this->browser = 'Lycos Spider';
            $this->brName = 'Lycos Spider';
            $this->hasGui = 0;
            $this->spider = 1;
        } // Offline Explorer
        elseif ($this->eregi('Offline Explorer/([0-9].[0-9a-zA-Z]{1,4})', $this->ua, $info)) {
            $this->full_version = $info[1];
            $this->browser = 'Offline Explorer';
            $this->brName = 'Offline Explorer';
            $this->hasGui = 0;
            $this->spider = 1;
        } // Openbot/3.0
        elseif ($this->eregi('Openbot/([0-9].[0-9a-zA-Z]{1,4})', $this->ua, $info)) {
            $this->full_version = $info[1];
            $this->browser = 'Openfind Data Gatherer';
            $this->brName = 'Openfind Data Gatherer';
            $this->hasGui = 0;
            $this->spider = 1;
        } // Scooter
        elseif ($this->eregi('Scooter-([0-9].[0-9a-zA-Z]{1,4})', $this->ua, $info) || $this->eregi('Scooter-', $this->ua, $info)) {
            $this->full_version = $info[1];
            $this->browser = 'Scooter';
            $this->brName = 'Scooter';
            $this->hasGui = 0;
            $this->spider = 1;
        } // Sqworm/2.9.70-BETA
        elseif ($this->eregi('Sqworm/([0-9].[0-9a-zA-Z]{1,4})', $this->ua, $info)) {
            $this->full_version = $info[1];
            $this->browser = 'Sqworm';
            $this->brName = 'Sqworm';
            $this->hasGui = 0;
            $this->spider = 1;
        } // True_Robot/1.0
        elseif ($this->eregi('True_Robot/([0-9].[0-9a-zA-Z]{1,4})', $this->ua, $info)) {
            $this->full_version = $info[1];
            $this->browser = 'True_Robot';
            $this->brName = 'True Robot';
            $this->hasGui = 0;
            $this->spider = 1;
        } // libwww-perl/5.52
        elseif ($this->eregi('libwww-perl/([0-9].[0-9a-zA-Z]{1,4})', $this->ua, $info)) {
            $this->full_version = $info[1];
            $this->browser = 'libwww-perl';
            $this->brName = 'libwww-perl';
            $this->hasGui = 0;
            $this->spider = 1;
        } // psbot/0.1
        elseif ($this->eregi('psbot/([0-9].[0-9a-zA-Z]{1,4})', $this->ua, $info)) {
            $this->full_version = $info[1];
            $this->browser = 'picsearch crawler';
            $this->brName = 'PicSearch Crawler';
            $this->hasGui = 0;
            $this->spider = 1;
        }

        // if we still haven't detected something
        // it might be just an old Netscape (pre Gecko)
        if ($this->browser == 'unknown') {
            // if contains Mozilla then it is Netscape or just the Mozilla browser
            if ($this->eregi('Mozilla/([0-9].[0-9a-zA-Z]{1,4})', $this->ua, $info)) {
                // if it also contains Gecko/ then it's a new Mozilla
                $dummy = [];
                if ($this->eregi('Gecko/([0-9].[0-9a-zA-Z]{1,4})', $this->ua, $dummy)) {
                    $info_1 = [];
                    $this->eregi('rv:([0-9].[0-9a-zA-Z].[0-9]{1,4})', $this->ua, $info_1);
                    $this->full_version = $info_1[1];
                    $this->browser = 'Mozilla';
                    $this->brName = 'Mozilla';
                    $this->hasGui = 1;
                } else {
                    $this->full_version = $info[1];
                    $this->browser = 'Netscape';
                    $this->brName = 'Netscape';
                    $this->hasGui = 1;
                }
            }
        }

        // now extract version
        if ($this->full_version > 0) {
            $pos = strpos($this->full_version, '.');
            if ($pos > 0) {
                $this->major = substr($this->full_version, 0, $pos);
                $this->minor = substr($this->full_version, $pos + 1, strlen($this->full_version));
            } else {
                $this->major = $this->full_version;
            }
        }
    }

    /**
     * Detect wether the user is browsing with a PDA
     * Returns true if he is, an array if he is using Windows CE
     * and an array with ["width"] and ["height"] if those variables are known.
     *
     * @return mixed wether or not the user is using a PDA and size of the screen if it is passed along
     */
    public static function detectPDA()
    {
        $browser = substr(trim($_SERVER['HTTP_USER_AGENT']), 0, 4);
        if ($browser == 'Noki' || // Nokia phones and emulators
            $browser == 'Eric' || // Ericsson WAP phones and emulators
            $browser == 'WapI' || // Ericsson WapIDE 2.0
            $browser == 'MC21' || // Ericsson MC218
            $browser == 'AUR ' || // Ericsson R320
            $browser == 'R380' || // Ericsson R380
            $browser == 'UP.B' || // UP.Browser
            $browser == 'WinW' || // WinWAP browser
            $browser == 'UPG1' || // UP.SDK 4.0
            $browser == 'upsi' || // another kind of UP.Browser ??
            $browser == 'QWAP' || // unknown QWAPPER browser
            $browser == 'Jigs' || // unknown JigSaw browser
            $browser == 'Java' || // unknown Java based browser
            $browser == 'Alca' || // unknown Alcatel-BE3 browser (UP based?)
            $browser == 'MITS' || // unknown Mitsubishi browser
            $browser == 'MOT-' || // unknown browser (UP based?)
            $browser == 'My S' || // unknown Ericsson devkit browser ?
            $browser == 'WAPJ' || // Virtual WAPJAG www.wapjag.de
            $browser == 'fetc' || // fetchpage.cgi Perl script from www.wapcab.de
            $browser == 'ALAV' || // yet another unknown UP based browser ?
            $browser == 'Wapa'
        ) {
            // another unknown browser ("Wapalyzer"?)

            return true;
        } else {
            if (strstr($_SERVER['HTTP_USER_AGENT'], 'Blazer')) { // Palm's browser
                return true;
            } else {
                if (strstr($_SERVER['HTTP_USER_AGENT'], 'Windows CE')) {
                    $extrainfo = substr($_SERVER['HTTP_USER_AGENT'], strpos($_SERVER['HTTP_USER_AGENT'], '('));
                    $seperated = explode(';', substr($extrainfo, 0, strlen($extrainfo) - 1));
                    foreach ($seperated as $key => $info) {
                        // We trim the whitespaces
                        $seperated[$key] = $info = trim($info);

                        // And check for size
                        $explodedinfo = explode('x', $info);
                        $size = [];
                        if ($explodedinfo[0] && is_numeric($explodedinfo[0]) && $explodedinfo[1] && is_numeric($explodedinfo[1])) {
                            // We've got browser size
                            $size['width'] = $explodedinfo[0];
                            $size['height'] = $explodedinfo[1];
                        }
                    }

                    return $size;
                } else {
                    return false;
                }
            }
        }
    }

    public function detectOS()
    {
        // Windows 3.x
        if ($this->eregi('Win16', $this->ua) || $this->eregi('windows 3.1', $this->ua) || $this->eregi('windows 16-bit', $this->ua) || $this->eregi('16bit', $this->ua)) {
            $this->platform = 'Win16';
            $this->os = 'Win31';
            $this->osname = 'Windows 3.x';
        }

        // Windows 95
        if ($this->eregi('Win95', $this->ua) || $this->eregi('windows 95', $this->ua)) {
            $this->platform = 'Win32';
            $this->os = 'Win95';
            $this->osname = 'Windows 95';
        } // Windows 98
        elseif ($this->eregi('Win98', $this->ua) || $this->eregi('windows 98', $this->ua)) {
            $this->platform = 'Win32';
            $this->os = 'Win98';
            $this->osname = 'Windows 98';
        } // Windows NT
        elseif ($this->eregi('WinNT', $this->ua) || $this->eregi('windows NT', $this->ua)) {
            $this->platform = 'Win32';
            $this->os = 'WinNT';
            $this->osname = 'Windows NT';

            if ($this->eregi('NT 5.0', $this->ua)) {
                $this->platform = 'Win32';
                $this->os = 'Win2000';
                $this->osname = 'Windows 2000';
            }

            if ($this->eregi('NT 5.1', $this->ua)) {
                $this->platform = 'Win32';
                $this->os = 'WinXP';
                $this->osname = 'Windows XP';
            }
        } // Windows ME
        elseif ($this->eregi('win 9x 4.90', $this->ua)) {
            $this->platform = 'Win32';
            $this->os = 'Win ME';
            $this->osname = 'Windows Millenium';
        } // Other Win 32
        elseif ($this->eregi('Win', $this->ua)) {
            $this->platform = 'Win32';
            $this->os = 'Win9xNT';
        }

        // Check for os/2
        if ($this->eregi('os/2', $this->ua) || $this->eregi('ibm-webexplorer', $this->ua)) {
            $this->platform = 'OS/2';
            $this->os = 'OS/2';
        }

        // Check for Mac 68000
        if ($this->eregi('68k', $this->ua) || $this->eregi('68000', $this->ua)) {
            $this->platform = 'Mac';
            $this->os = 'Mac68k';
        }

        // Check for Mac PowerPC
        if ($this->eregi('ppc', $this->ua) || $this->eregi('powerpc', $this->ua)) {
            $this->platform = 'Mac';
            $this->os = 'MacPPC';
        }

        // SunOS (All versions)
        if ($this->eregi('sunos', $this->ua)) {
            $this->platform = 'Unix';
            $this->os = 'sun';
        }

        // SUN versions
        if ($this->eregi('sunos 4', $this->ua)) {
            $this->platform = 'Unix';
            $this->os = 'SUN 4';
        } elseif ($this->eregi('sunos 5', $this->ua)) {
            $this->platform = 'Unix';
            $this->os = 'SUN 5';
        } elseif ($this->eregi('i86', $this->ua)) {
            $this->platform = 'Unix';
            $this->os = 'SUN i86';
        }

        // Irix
        if ($this->eregi('irix', $this->ua)) {
            $this->platform = 'Unix';
            $this->os = 'Irix';
        }
        if ($this->eregi('irix 6', $this->ua)) {
            $this->platform = 'Unix';
            $this->os = 'Irix 6';
        } elseif ($this->eregi('irix 5', $this->ua)) {
            $this->platform = 'Unix';
            $this->os = 'Irix 5';
        }

        // HP-UX
        if ($this->eregi('hp-ux', $this->ua)) {
            $this->platform = 'Unix';
            $this->os = 'HP UX';
        }
        if ($this->eregi('hp-ux', $this->ua) && $this->ereg('10.', $this->ua)) {
            $this->platform = 'Unix';
            $this->os = 'HP UX 10.x';
        } elseif ($this->eregi('hp-ux', $this->ua) && $this->ereg('09.', $this->ua)) {
            $this->platform = 'Unix';
            $this->os = 'HP UX 9.x';
        }

        //AIX
        if ($this->eregi('aix', $this->ua)) {
            $this->platform = 'Unix';
            $this->os = 'IBM AIX';
        }
        if ($this->eregi('aix1', $this->ua)) {
            $this->platform = 'Unix';
            $this->os = 'IBM AIX 1.x';
        } elseif ($this->eregi('aix2', $this->ua)) {
            $this->platform = 'Unix';
            $this->os = 'IBM AIX 2.x';
        } elseif ($this->eregi('aix3', $this->ua)) {
            $this->platform = 'Unix';
            $this->os = 'IBM AIX 3.x';
        } elseif ($this->eregi('aix4', $this->ua)) {
            $this->platform = 'Unix';
            $this->os = 'IBM AIX 4.x';
        }

        // Linux
        if ($this->eregi('inux', $this->ua)) {
            $this->platform = 'Unix';
            $this->os = 'Linux';
        }

        // Unixware
        if ($this->eregi('unix_system_v', $this->ua)) {
            $this->platform = 'Unix';
            $this->os = 'UnixWare';
        }

        // mpras
        if ($this->eregi('ncr', $this->ua)) {
            $this->platform = 'Unix';
            $this->os = 'NCR mpras';
        }

        // Reliant
        if ($this->eregi('reliantunix', $this->ua)) {
            $this->platform = 'Unix';
            $this->os = 'Reliant';
        }

        // DEC
        if ($this->eregi('dec', $this->ua) || $this->eregi('osfl', $this->ua) || $this->eregi('alphaserver', $this->ua) || $this->eregi('ultrix', $this->ua) || $this->eregi('alphastation',
                $this->ua)
        ) {
            $this->platform = 'Unix';
            $this->os = 'DEC';

            if ($this->eregi('ultrix', $this->ua)) {
                $this->os = 'DEC Ultrix';
            }
        }

        // Sinix
        if ($this->eregi('sinix', $this->ua)) {
            $this->platform = 'Unix';
            $this->os = 'Sinix';
        }

        // BSD
        if ($this->eregi('bsd', $this->ua)) {
            $this->platform = 'Unix';
            $this->os = 'BSD';
        }

        // FreeBSD
        if ($this->eregi('freebsd', $this->ua)) {
            $this->platform = 'Unix';
            $this->os = 'FreeBSD';
        }

        // VMS
        if ($this->eregi('vax', $this->ua) || $this->eregi('openvms', $this->ua)) {
            $this->platform = 'Unix';
            $this->os = 'VAX VMS';
        }

        // SCO
        if ($this->eregi('sco ', $this->ua) || $this->eregi('unix_sv', $this->ua)) {
            $this->platform = 'Unix';
            $this->os = 'SCO';
        }
    }

    /**
     * Replacement for the POSIX ereg() function removed in PHP 7.
     *
     * @param string $pattern POSIX extended regular expression
     * @param string $string
     * @param array|null $regs receives the matches
     *
     * @return int|false length of the match (at least 1) or false
     */
    private function ereg($pattern, $string, &$regs = null, $caseInsensitive = false)
    {
        $regex = '~'.str_replace('~', '\\~', (string) $pattern).'~'.($caseInsensitive ? 'i' : '');
        if (@preg_match($regex, (string) $string, $matches) !== 1) {
            return false;
        }
        $regs = $matches;

        return max(1, strlen($matches[0]));
    }

    /**
     * Case insensitive variant of ereg().
     */
    private function eregi($pattern, $string, &$regs = null)
    {
        return $this->ereg($pattern, $string, $regs, true);
    }
}
