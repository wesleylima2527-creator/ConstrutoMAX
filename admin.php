<?php
// Painel admin da ConstrutoMAX.
// Mesma pasta de: index.html, db.php, admin_auth.php, admin_api.php, admin.css, admin.js
require __DIR__ . '/admin_auth.php';

header('Cache-Control: no-store');
header('X-Frame-Options: DENY');
header('X-Robots-Tag: noindex, nofollow');
header('Referrer-Policy: same-origin');

function h($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

try {
    admin_session();
    $logged = (int)($_SESSION['user_id'] ?? 0) > 0;
    $admin = $logged ? admin_user() : null;
} catch (PDOException $e) {
    error_log('admin.php PDO: ' . $e->getMessage());
    http_response_code(500);
    exit('Erro ao consultar o banco de dados. Tente novamente em instantes.');
}

if (!$logged) {
    header('Location: index.html');
    exit;
}

if (!$admin) {
    http_response_code(403);
    ?><!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Acesso negado — ConstrutoMAX</title>
<style>
/* =========================================================
   PAINEL ADMIN — ConstrutoMAX (tema premium)
   Mesma linguagem visual da loja: Archivo + Instrument Sans
========================================================= */
:root{
  --ink:        #111214;
  --ink-2:      #191B1E;
  --stone:      #F2EFE9;
  --paper:      #FBFAF7;
  --sand:       #E5E0D6;
  --muted:      #6B665E;
  --accent:     #E8590C;
  --accent-deep:#C2480A;
  --accent-soft:#FF8A4C;
  --danger:     #D64545;

  /* nomes antigos usados no resto do painel */
  --black: var(--ink);
  --black-soft: var(--ink-2);
  --cream: var(--stone);
  --cream-dim: rgba(242,239,233,.62);
  --orange: var(--accent);
  --orange-dim: var(--accent-deep);
  --orange-light: var(--accent-soft);
  --steel: var(--muted);

  --line: rgba(242,239,233,.12);
  --line-dark: rgba(17,18,20,.09);

  --font-display: 'Archivo', 'Arial Narrow', sans-serif;
  --font-body:    'Instrument Sans', system-ui, sans-serif;

  --radius: 10px;
  --radius-lg: 18px;
  --ease: cubic-bezier(.4,0,.2,1);
  --ease-out: cubic-bezier(.22,1,.36,1);
  --shadow-card: 0 1px 2px rgba(17,18,20,.05), 0 10px 28px -16px rgba(17,18,20,.2);
  --shadow-pop:  0 4px 8px rgba(17,18,20,.06), 0 40px 70px -30px rgba(17,18,20,.55);
}
*{ box-sizing: border-box; }
[hidden]{ display: none !important; }
html{ -webkit-text-size-adjust: 100%; }
body{
  margin: 0; background: var(--stone); color: var(--ink);
  font-family: var(--font-body); font-size: 16px; line-height: 1.5;
  -webkit-font-smoothing: antialiased;
}
h1,h2,h3{ font-family: var(--font-display); font-weight: 700; letter-spacing: -.02em; line-height: 1.12; margin: 0; font-stretch: 112%; font-variation-settings: "wdth" 112; }
p{ margin: 0; }
a{ color: inherit; }
button, input, select{ font: inherit; color: inherit; }
button{ cursor: pointer; }
:focus-visible{ outline: 3px solid rgba(232,89,12,.5); outline-offset: 2px; }
.skip{ position: absolute; left: -999px; top: 8px; z-index: 100; padding: 10px 16px; background: var(--accent); color: #fff; border-radius: var(--radius); font-weight: 600; }
.skip:focus{ left: 12px; }

/* ---------- Marca ---------- */
.brand-mark{ display: inline-block; flex: none; width: 40px; height: 40px; background: url("data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAMAAAADACAYAAABS3GwHAABOwklEQVR42u2deZycVZX+v+fet6q3pJPO1kkI+wAqCDi4ISCLKDqKCorjziC/QQmrKziKgqLCKM4AErYBBAdcUFxAEZFNUGAABUVWCZBASEjSnXTSS1W9957fH/e+b71V3QlbErLU5VOku6u6uqrec+59zjnPeQ60Vmu1Vmu1Vmu1Vmu1Vmu1Vmu1Vmu1Vmu1Vmu1Vmu1Vmut23U2TG19Cq21ya2Vj55x0/Ib9tEnvzFNn/3x57T1iaz9Ja2P4OVfK27aaX/adrs+XfBjOhlBHoHBJ2Fkq9fQ8caP3jxx38/u2/qUWg6wUa7Bv5+itccvpWt4LlIC1YT0QTB9UNOUYdowr/kgkz96aetatRxgIzL8f5x1r3/ih7uU+m8nSUAkIcVh1JI+BGZpim+zlJynOqKsmLQ9pf2OZ9Kes1vXrOUAG+5a/MdDjyiNPHt+edn1lEkxxqIqeHEoAmJxDytmcYpPwKpBrcVUawzWIN3p3ayszn3rVkfd//vWp9lygA0ryP3LbPWP/5AO349JLKkxiDpEQbSENykC1B4SksUpaoXwn6JGsF5IhxwD3TMo73ckE97yldb1aznA+r9GnjhXRx6ZQ+fA3yiVBSXBew8oYMKFEInfC9WHwC6uQQKmkAtSIMGA9wzWYGibvSntuPc5PW/52tGtT7nlAOvdWvbwiafJovtPaFv0G8p4fLkNwQGKambZox2g9nBwALWNDhCcQPBiKAG+4lhR7sDvfgST33vmOr2Wi3/9FdXHb6bcVkZUKb5MMYJI+JmqxhNOwrtTUAFJwn3hcxCMWrzUGOycyWYfvmKdvZekZaZrKci9/3OaPnwB7dU+SkkCtGN9BW8oGPzYe5AYEGOiQ2iTA4Sf1TCYjoRxOkzlD2fSd8Yuyhs+cc+kPY977TrZOZ+5g65H/oDpiO9AofiWtGmXzR6j8QdiRj+nqYEb37tOr5NpmeqaXUOPnaMrrt1JSw99h/Guj8SW8GpwUsHJ869tSdxJm61IRbDqSXCoehBLZ4dl4rP3YX71hd36f/AxHehftNY3NjuuE+02SFsbYi0msYi1iE3y703+8wRsAkmCKZUwSYIkCaZwkyTBlw1JqX2dXq/WCbCmAtz7jv+SLnnwVO45ji5NEVPC4UEVRVGN216EPvUToMnw1UD4TQyCGmnYTQ3BCRDBAKJxW7WWCVSp3vsDVsz7v9qKO85n/Bs/udaghHGKUY/gEB8C+eihAecUXnW4S3IIl72R5lPCeI96Wg6woa0V93xR3UPn0+X6sNbgMKApxgVDza6prA1zzOGG4jHYDqFn4GGGf3okSy49WJc9edVr/+kr3LP2/nB8U5odUQbUo6OgW3D6zPHzGCh7mA3/+HXsAC0I9BLW8r+fdW/fL16n5t5v0Z72oWKpqaLiMapIalCvmOyD9qMhUGYUIhIPh7FhUvFxDb9TuCHhb7iSpatNGf/Xq5jgZ9y9/JbT1jivSDW+XjGIGrxT1IP3ivcC3oA3qDOoN+DJb+oVHOEWk2Hq67eWA6zna9GPe/dYfuPHVW7/wi5d/XdRLlusLyGqGBWsN4hanPgcosiLMMGwaxqMeQGXSTzioUaJckeJySPPIL8+kb4L99Olv5v9mTW2/2tm1D44I4KmCk6jkQvq44ngBbzUDbzh68zww8+MmJYDrNdw589f046Kva3jscsYb6uUTDlcdPXgBYkX3cd9XwD1HhQsgsSLHmC8UtwajQmnB6rxwtQ9p3nnrwMRjbfsaQwgiHqcKi4p0dFuGffojXDrT87o/82X1shpIDXBVsGkGv66SVAVxBFu8bMQH1KgohbxFpxpuPnUoKkgzoBrnQDrr+Hf++3frPzZm1Xu/ApdlQUk1uIRUF9IZI62Le99uKkv5P7X9qoHoaIecYotl+hJ+yhf/w2WnPU6XXHj1y56aX9B0BQ0FbSmiJOQ6/cSHNHVT4PgFNpwyyFQvGmEQ6LScoD1ad16CDOWXX+Yutu//I7ywj/QZQxqDanxqBe8+rgPh6JOMPhY4FElj/V8uC9zAuccPhaCirdYC1sN9tZV3sY21BiBqFKTEh0dlglP3oW74fRPLL/qmBfvkTWPrYBUBaqCVD2SBngTjFoRnxl7/Dre8BqySE6xXjGOPIbAacsB1pc1cO9/6qsO2HpBx0OXMM5UwJbwKOrjka0uGp9Hva7DHf6F7dUq4FEMVVL1aFvCOB2k9MezWfyfr9SBO+c8/YKftuphBGQEqCq+pnEXz/C95hh/VJDrG3f/AN20BYHWl9V38xHHLv/F29Tc8gUmjDyOtRZ1gmgaUpoZ5I/BoCAx9W1AbAAfqgH7YkFtXgVVTMDKasHZ8PuFQJE8GyQYkYbDwKtHtE6P0AyKEGsFcbMvxglCrBXE70LA6lExtHWU6Ol7EP3ZsTOX/uBDeiv0PP8sUBmGwVcUP2LQEdAKaOpRp4g3iDNIKpAWAmEXbz5kh7LvpWagClJdtx7QqgM07/o3naT8/Rzaqn2YUinYpM+i1kBkyfgsoegzGqKgmlleeLxoYHZqMXhdt1hXmiMUBedTMJYuFHfPD9nxlC37BvY5lu69PyvP/XwltApSDs8cfFbjrhA/l6zYJVr441p4RdRTvyaLAVonwMtj+Hefde/iS16ves/XGVdbhpi2gO9zopdg1GAwoOFrUWnA3/VMTDzSC9VRyY5658GFwNR48rig/jwhbhgL40s041Xh/tXFB2PBMxHBoIhA0lVm4vCTmKs/y5Jz99EFPzvkY6v7vNKqw1dBqhZTNZiaRVIL1fppIKmJGSCpfy459NHCDYwTjIJfx0HwJn8CLLq8d4+kY+/b9IYvMM6MkJQsNRTjaiGdabKUZb3iKcF6GjY1JdvtG3ddVMiuqahEGjRohmWagl7vFROQ0HPFw2tk+bgNGq3hbEKX9ZTm3szQoqmXrbz+pMvGvfXrY74EV/X4KvhqeO9i4i4u8X2ioC6cEAVKqzadBJljmizrSysLtM7Wsj+drm3Plm/reOAndJkKVhLUgaQ+XilHRmRQBE9WdQ14HCOB6CUWSMKp4OOOpwbnwMVAGY3ZDwKHR3F4dQHBRyMo0mg0fu+bTgOv8XVkRaQs1Rh32rHTiBLxv4m3ej0hjzPiaeABW7aMSxdjb/w6/XP20OW3fuPyUY5T8VANO72retIRxVc8UvUxwwO+6mNxTPJbVh/ITgKJadMUBQ1xQ8sB1vJaeNOJpz170Z5qrzuBrqH5mJIl9QnqQzYn34d0VTuw5jeNqU6pk35HPbLhe6/xb4SdUwupUXT1vzsmFhfBGLNKct3zjQ9yTI6g6nDGUi4Zxj/5R7j2mx/uv2p248upOPwQVKuOWtXhayET5POMkKKp4kdiejRCv+zWnBVSB5qGdGgLAq3F1X/tcer/dBYT0iGkQ6iIxfoUg8fFRo4GC/SQMxHibpyjIa+IeoxIPDHqMIAsFpSM1y/13xXBeV/nxCsYAecVNSZmghze+1ENMUWvyIzeex+gR/hf5BUVYpMm59DnYKSqGEppcAJTLtHtBxm6fQ5L/3N7Tfb5TP+E139qUi1tp7oSbMmjSeTAGVAbdnpvw3vX1KPqkbYmrlNTTCIuZJB86tZ5cmCTWEN3nasjd3yPjiV/p73NkNoy6quYGOQ6CVhVcswfcbgIYgUxhohsEBMpzviQAs0MSQisRgM+8h2yxhbJjxSN94e/ZQUwJkIsD15JH4WkH3ybCQFz8YJlrEsZfSUzB6CJcdls6LkDr6p4luHyyG52ImiilEY8A2mZ0msPYeD6x0hvvIPx4y1SBhKPNYIxiliDGrAm7CCagLQbjJF6Pqo53SMJpFWWTZnFtP95SloOsIbWgkte9/ZOmX5t8uhvaC85pJQEtqLNDErxQuDgmNiEIoqKBpxs6phZTfi5mpjINNDI1s+S9OHfkJs3wZnyNsEQO3hjMbhwBZIEqFGpgE7fFR3cluHbf8u4kUHaygYEUqMoJjqsFvL8MuaVzFoNG4LyZiryKo1CcZiGk86o4q3BeoerKTpcov8fntrTnnYxlBKFJBi/sQLWY7L3LAoloVSyKC6cfIXX4gUMJSStMTBlM6Z8f36rJXKNpDZ/f7L628+nY+guSm0GLwm+5vOcfGY8GSzRQsZcx9gh8ly6j22LY+QsMtuSUCWrUyGcD45jI0oSxZkEIUWrNSod05Gd/h/jdjlV4F6WXjv7M4PzHz+j8tC1dFoomQSHxxttDHRXayovLo+khIA4+xCyz0e8DyS/sqDllCmvKVGb2c6KuRUqSyCpWaSc4hMFLxhbOPmchgA4IVAi4mmlsRdaRXNWaAsCvcS18s5zF/k7L5pWXnA35RJ4EygMIgEreylUS4Wws6Nhtwe8xF3KmLwtMZwGmv9+hrUbjnIp4n8f0p82XGAxAVJ4EryFRKqkHiqlDnSrj9L3xwu33fIzzG1+L8v/fEG1euP3SuMW/pX2Mqgp4dSvtQuXxQ/NP8tjjejlzodNIrEWqSUMzlVWPpaSVKDcJviSwxgJp0BoXQNRkjaDSaTQT5PBOUFcysDkzZl2+bwWBHox67oZzNjtG+9b0Pbgz+g0oG1JMGx1pMZiY7LZCwWjltwBbNjW8fGY9kYaHMDYLC6I+fNRzd3xB+Lj0Q9YrTuAsTgLztdIfQk/423ojNec2b3Tqcc/13vr+83n1d/5P4wf7qdUsngxa4U4M5YDNH/vCRkeQwj+PZCQUF1uGXwcqguqlBOwpWDcMa4PMZOFcrtFjcbPUCI8FMTVWDFlc6ZdMb/lAC8Y7tx6rlZ+dzrtA0/Q0Wlih5QL2ZXYMWWsNCVSFGx0gjzoU7xo3bBFgxOYDPPXG1XIYgKaMj/xpHBiMAJYjyQGfEoNqE58HbLdoXPHv/LobV/Ie1x83eGzeXrhOaWHrqNbU2izgVEqimIxGvoAtBjkFoLiF18tqxetRCR867NGn5jdUY8YIaGNoQWeZf9wmJVKKQGTEE/acHIlJYMt2wiBPBglkRLW1Vg+aRZTr2ydAM8/rXnTV75Xuf/Oo5IHb2J8Rw0phSYMI4JPXLhgsahkEtu0s/kAUeIxLCY4gBYcQKKTYBurlCZ3AJ8/v6CIVVQM4iO0sklIsapjxfhZtO9wBON2XbWi20D/oq8CJ3f39K7yMcv/9L3HardfuE3b0/fRmQDlUt59psgoMa2Xyi6QJgcIvf5ab4uE0AIa/6KxJdxQOyv/UWHo6Solb0lKnqwzUoyJJ4RBjQMDloSSS1k2aSbTft7KAj2vtfQXn1b+eAHt1UHa2iM2wYdaq4BaBWNCWV4Iu3BDsOfzI5hC9icnssVgNUttZvyEUEEdwwFiUOeNwQIiDlfzVEtd+O0+yJMPXLTtzrNH4/xo+LOBc5r9u7und9IqHeG3J6i/4/t0rFhEudOQShnjquSsTwl9Cq4pHfqCaduxo62Yis0cgAiDpMCHUq+IBStlRhYaBh6toctdSJMmJv+MS20GbJZ6luAAkzdjxi+fbjnA6tbgrWdr5ZY5tC95kKQDUkloq6V5BkPFgklDDj/m74MDyJgOQIb1n6cDZNkVY0PtoO4ABkSxVnHOM2jLMHNPzFZvuaJ7xy99ZBWGvzdw83O85aO6e3rnjHXH0+f17tE1cf/b7N+upL1WxbSVggFmBbExHGBV2P6lOkCui+XBq8GLJ7EGP1xm5VOeoSdrJMNKqWyo4bGJxFPAY6xQSh3Lpsxg1jXPtBxgrLXsN8ecIE88eJr5xw2UjGJKJnZdxcJNlN9DJGBLsjx+NN6kKbjLsg/xZJAM08eMRQ6fDTG/Hy64ZlkkA2rTaAQWrGC0RrUGI5N3o/21R9C+wydXB3de0Fa8Oli04o+n/2rknh8d2D7/L3SVgSRmvmKjTgj8Qx0hq/IVHWBMtmhTDNDwWM044YHzLE5z6cNQMo99EaShKEaCLi+xbK5jaKGjpB5bVtQaxBhsQjgBeqaz5e8WthxgFNb/5edV77yI7sE+aEvwxjGaPCOjvs0UFcRIzMgUGOmZ6JQ14eKZjPsgoSim2QkR89mxkumzvLj1eBGsSTBapVrzDHZOJ9n5E3S/4ZtrzPBfiCMM3PJ1rd5xEeP6nqCtDWpSwniXQxMfgxtjTEOl2I8hyJM33ujqHUBUwGmuA1o8YzUr+kowdus7GFroWTG3CgPhFJBEsdZS8inLJvayxY2LWg6Q7/p3XaDVm/6bcQseoK0MXhJUPQbfoLOpOrY/mMhtEAEnPr/4ObyRgvqaKJiQzQjMTM1jAzGBEqForsymRkgMaFplZbkdv/X7GU4ffsfMd93121UY/u3AG9fQR3Nid0/v6WPd8U5ov/Qn/z7MfT9ioluBlBO8lhBfwRkQQnaqaNdjwiHnx4RO+anrs+MxcnmaHKDYx2BUQjYOTymx6MqElU96BhfUKKdK0m6wzrNs0ky2uGlBywHm/9e2e7d17Xpz2/1X0WkVKSekRjHqIl24QFwL4gyj6rIqTWpk0oSFo2FrxMoSuUCB6hB0N0MSO8AiE4taXgSxBlyVYbG4Wftgt93nsu7XnHToKgz/M8AZa+NzWt1psOyu71xf/fM1+3fMvZkOAVduD/MIGDsozv+NVWCzqnMqo2fHZgJBxnSAvHimWR0spqdVSfCIlBhZmjAwt0plpaNDYXjaLLa4aRPPAi2/7hRNbz6XzpULKbdZUisIDsUgolj1USa88C786LfT3LE4Kh1uCzBIQGzEx6b+uy6R+DfBxXRo4h0VpwxO3IHkn49g4m5jtxAO9C9qB4bXxWe22rTprd/V2p3n0vHso3SUwCdteO8wonhs2Ai0YNwRDgkyirWZHZR1Wnekv3piMNzsABEqaaRXZOXy1AX9I2PQasLKRQnV+UMMjduMrW7bRLNA/Td/99ba7T/ds/zUn+gqg9oksiYzC/eNolCjshqrfzsyVsNpjA1CKjSKj4uADdDJSg0vFp+UQFJ8rUalazKy8+GcuPd/dpwLI2sD57/IdUV3T++Y2aZ7jmTGNm//9wX+np8wrrKccjvUSDA+C5AlN2zTvIM37xtRFS7vbov1AFMIsBquhYI4aTxBssySj8mGJIFlyryBaWyzqWWB7tqdzbf+1Cfmuf+7jAmaYjsCztcsDbOWHECjc6nRPA1ab+gODmFog0Sp6Qg1SahtewB+xmbHTdnjgrNWYfjXA/u/zB9pqbunNx3rjsXXHj6bpX3nlB+8hnFagyQJpG6fBbbawNTMtYoKxm0wof+94ABxGkZdJdo3mZmXQkxA1P8JQbLxgqqQWM+wa2f5Lu/jumMvn3YYLN7oHWD5TWdr+rv/om3xXNq7hFpiUTyJRspllsfHBzLbKl+yrPK+erZDR/1KiAGyRhYNzS0iqDFYCyIpQx5qm+1O+bWH9ne++lOTVmH4xwJnrk8n6mph0V3nDaS3zRnf8cxfKZcBW8a7IOrbvPtnbZ65PEBzdsjXHYAYHDfR6QL+LwTFQUUuyLzk1G0Fi6dShYGJm5G89ZNMPnDtzkF72Rxg6fcPPUKXP3N++4O/o60MGEtE9jHXLjlvPKcZ5E3kq25UXP2bHYPUlWV5JNInjEWMYnF4hZUTpiOvn83EPb+y1tKaaxtZrraafOPJ6u44n7aBZ2grCZ4kUh+CtqhRMGqiFpI2tIkWHSDfW2JLqRb3qngya6RQmEgFzVpDRRXjQ6+zw1NGUOcZrMLwDm8k2XX/Mye/57kJgxuMA/Rd9R9au+UCeipLMO0WL0Emz8cikxY6svIXmjEKIx9lFPwhkNhWt5oboD0mFMxEg9yJKWNNSi1NGWyfgNn5YB6+7ZId3nAGj2yAhp+tv3f39O60ugc8cnHnGyZ3vOcO89ef06kj2KQU1Ck0jSmxGAxnJW/fdLKqNMzECNy4ZngalMTE50y9xmsRe4OMxseqRUigUqG/rZN030OZcei5skE7wPIbz76/8qfLduyYfxedJUEw1CQJk0YkzRUXMpWzxm4mjddhtANkJX8vq+e8mDGyGV6Uqm2jTTymVmOFSUi33Rd9xe7nTNlr7OmLA/2LfgO8Y323/NVBoDHf1x9P/1Xtb9cdWH78Rro8qC1RE4v1tdi1buoaP2M4QHHoXcwlv0gH0HiSWDwWS5VKBQZmvpLOdx3FhP2Olg3KARb/+L0f8n3JFe33/JIuU4N2Q6pgvcEqpNbX4Ul0gmJzeW6too09uM/hAKPTd6OpEIoFlzLsoLLZqynvdRQTX/+pVaU13wVcvbEZ/mhHOEPTG8+mvOwJ2ttMqLFEzI5vlF7xEcfntGgaHSDH/VnvkKN+ohRrDxFGZYzWVCGJ6W4QSprgtMrKFAZ3eg9Dvu/A7U+69Zr13gGW/+ok9TfMoX35UpL2EmoVE6UGvYDgcCJBOz9vkQtU4kznsngCjJ3dGcsBpCEykAyEEvQ5jQjiHcMjMNw9A9nrcCYfcOqGivOz9cnunt4L1hxU/ZTKn39M51A/5ZLgvAk6RFmMFtOZoxxA6xXizAHyFKkf2wHwJsKomB1y4CVQu4OgGIixGFJqI56B9qnI2z7BtI+dLuulAyy+7jvX620/3b/r8TtoawMpl3AF2mx8Z/V2O8hFm4IDeFR8wQGykUDZ7l3QwM9aG6VxnFwIt9JA+VfC+CKTUPKOtOJZWRqH3/W9PHbb/+74hnN5YBWGPwCMX88N/7bunt691soGdstXvpc+8MejSo/cwjhSSBJSF9KdVn0MXAXjYyexp6EVOYMzpikm8AUHEMCktvHE8E0QKtIyfCyeGedYOQyD27ye0uv3P2Pah775ufXCAd4J7Zdd+G/D/PGHjE8rSHsolattjkgb4UpolJA16AAS/wtdSCqGVAymWmW4BiPbvZVkl70unrTXVw7fUNKaawPuPN81ePeFWrn5HNrm30u7DRtM3iJMbHgvOEBDpsjrKMU6zRjW2QgpJwU4pBjsmA6QM389YMroSIW+jm7cvv/K+Pefusr6xzpxgGXX/ZdWrv0vup6ZR3uHJTVCogZn0qAeWIQszRkbUxd1Cpk1FwpRxjynA0Ahn194Y0Z8TKdafJpSG4HBaa/AvvnT9Oz5yVXh/HHAipbhr+IaX/1p9X/8AZ0DS0jKBhWDUYdq4I+op15UK+anVRpFfn1goOYbnG9kpJq4Y+Y/GzVgMATjRgWnjuGVsGLGFnR84ASmvv0oWacO0HfViadV/nb7CR0P3kKXDc3o4hyIkCYljHqMcXWR2IIh1wPS0JieyfwFByCKKb1wBwABa7HUGKootY5e9PX/xqQDT9/Qcf6p3T29J72cL+Cp7+60f/v4V17f9reraXMjmHKCqsE4H6CNRkn5HAeNdgBxNsw7yBIWcYLO83UAicKpoooL6gNoJWVFFdI3HEil8sA+W5/+2C1r3QEWXXCc2psvoas2QKnDkGYBa+yl9VFqxIqNTSqRWdn8p7PfiexMIdCPs4SCSNTQzApjprkeFppgVMBiMXhqqWPItlF71TsZ9vPfscVh64SmvNHt+quMD24446bK7T/cp/2pu+myQKmE8w7xHnGxeR4TZM9jcKyOwkAR8oGA3jWp2cXNzWeG75rfus/0iwMviUDztmoYGUwZ6JlCcvDRTP/gybJWHKD/d+dr5Zf/Rce8h+jqAF9OUCJuz1QWTBDDyb5evQNksuH1jitjTb1JPZcWHNsBBKgZGxq/qlWGHFS32hP7ukPumbjHca9dheHvBtzdMvyX6AjXfl3TWy+ks28epVKYhoNTvCrOCMbFHVyjOnTDybAKB1CaToAmbaLsMT47XbKYIyQ/TLXGYBWGdtkb85rdT5/1sdNOXCMO8NT5Hz6s/emlF/O36xhnwZYsLtO+NJmigmCy8DMnlkXVBYI4laHpDRsd5QBigl6mikYopLmaWNEBQi+uwWuNtKoMTtqe8r7HMmGvozd0uPOB7p7eKzeA18mDH2T76W9538Pyl1/Q4R2JsYhTvPExNoi7f5Rw13g51YeBsupNY12gWSPXa4MDaFZ9zrJOxVmdUd0+waJDNfo7x2P2/yjzfnTuFq+7nfkv2gEWX/Yl1d+dx/gVS0k6hFSSCFdqwaBzBzBYrdMaMHFgQmw4QcbQRn0RDkCMCxLx1KqeoY4e/BsOZdJ7/nt1hv80MHM9t6eHu3t6X8EGuFb86byByvXnjS8/cS/jjcEnJjbYxNGosdSvIs/hAE3NOs43xHxanDyTab9ElKAaZxKoQVCsOoYrsHLmDrQd8un+Ke+uExqflwMsvOq/bh/63fffOGXefbR1QWpthOuKEw2iCRF+SGw/VHUNlIVs7oFk6mxNxDRjaGBlSiYrGDU7xRQEqAScTUgAW60xUGrDveIAZPLUoyYddNGcVRj+24FrW3Bn3azFV5+s5qYLGbfsaUplIXWxiCZBqiaMVA3MUSHTGqqbpYk7fbbj+1gDELJB4GEGQRhXVTeMeio1/C8cEoIFfE1Z6RKqe72XzU++Up7TAS6BqQec+qFn7Z2/YLwZxrRZUoUwOcVEpTSfN4uHlKVER/BNDpDl7MMLa6pzrNYBoH6SCEF12HtPpQYj2/wzdvfDHu7Z/ZhXbOBwZ8funt4H2IjWhdsy470nfmSBufNHjK85EmupWLDeQpqGCnHkF4VZaZIbsnG+QVzYo/n0zNCIHx0grzE01xm00JwTJus4sbRrSnVQWbblK3BbbfOBVTrA0p+frunPzmbcwqcodZWo2MDHT7yNWvpB/7LeWB4D29zofZ7DB/BRLtDE5pZmEWDF1+GRAWMlEhEzBzBYA0ZThlKoTJyO2esoJh5w0oaO85+Trbmhr74bvnvryB+u2HP8I3fT1QbO2jg6KY53ijkf7yk4wGj7QE10lHp9obFXQQuz3ALBTtXHynLs/VZDyRpWDlZxH/j06BOg7+enXZXe+vODyn+7k/ElqJVLqNPQz2kDlPGEIdESByJkCsuZunL2IkxhtEoqKSKCteYFOUCmwFASxVU8A+3jSHc7hOH5l7x2yy9yzyoMv1XFXR9h0c/+Q/1N/0P3kmdpKwseg0/9KhxgFQ37XkO8kPG+cgeIGcZcJ0HAGbzWp3CKKh6DYPEjKZWDZzc6wILvHqH87lIm1iokHQYvlhi64yUJQa5xcSSQiezMTEkhmypickFWkUxKVnHG54FtFn00sjYdmmj958YgRjHW4NPYlbXDPthX7XfxxLeNTV/YgHb9Lbp7euezCa4nv9K7R+fUt95m7/wZ42oBVvsUjPc4SuDTEPN5m0OYYsVY49iorPks61fICqn5wMDgNfkJQCTUhd5lgx92VN4bHaD/F9+9deVV39tzwuNzSToELUmevcyfPApE5ZPICyMNM1HZ4q7vqctiBwdo4vHHdsTc/I1ijSMMTbckSTh1KiPK4GavpLTfUUx48waf1jynu6f3aFqLpTee/it3408PHPe3u2gvQ2rLYUqeRlU530S5jtMs622Uox3Aa52uLQWhqGzuso+Qy2BJhx2V934K6bvyWz8Zvvj0Q7qry7AdJUKU64KceIGarFm3ggnqvqboABIwvsnk9iSLBeq9uGFoWp3WoAUmaDZoQgz4xKLiIHUMjp+E7vFvTD74u6sz/POBI1pwZ8NcS/73i+qvu5Cu/iW0dwg1Uwpz0WLAmw0Sl0i/Jk6R9IUh5KFu7HO59mK7pVLQK/KRq4ShNpRSPfiTJG7xkkO6Vi5Dx1lSl2JjM45IEIAyJpvY/MI22JwHbmjAcPkwt+YlJkiJV4dZWS5Te90hpBPNJ2Yc/N1LNuRd/8Ua/kD/oq8DKws/qsV/m2VY2oFSvL9E6AG+oOm5psZNIm16vrGeq6Pw/Qzgmu6e3t+urc9nyke/JY9/e9u9bfntN1f+cCXjhivY9jZSoXEASBxhm2sXj+rwbmSg6ig902LaNTy1MZbEaigyJTWPSlDqDcYeCxPZSIhMCzKjwMZzSLIBEXFSSCaNYSIFIktdmYwblakxq8NbQ0JQWVN1DI8MM7zFqyi/7Zhnp+55ZO8GDndeNGntpb6/gf5FezfpAz37Ep7uKNZy49TWn3/sFnhM+q8946b+G36yT/tDd9KZgC2VqfpIc/BgnVCzYWBf1nRTzwgVRsOqxHnHBSGuTN4lc44oLZu4tJYrBgseq5J3rTV7V56HjY3LYsifXOoPQGIris9eRNz1VbPurTAry6qA1KhWHcOTZmH2O5xpB54ifO3IVV3Y04ATWnDnOde0DREO9bzjs/sC9P/yNF3+qwvoWDCXUhsIFqdhcqV1WSehFGTata5XqlnxtGj8mYq4i5SzeobJeOcYrX0ko6BM1q8pURNSM35HdArxBtFwo6ABU8RhdSlzQwmhWqsxYDqo7PlvLKs+tcvEA09ZJUc/7orru/F3rCdYf5gNePW850SZctFcGXrb4Sz37ehKR0kNzoats5wCrqhSrQX8L2gaJ1Fmt9iUkxXa8sk22qiCF+ZNNcksZy1rGRkzN+Tcu+rBSOYADZ5ZxGYSqsVSq7Ki6hjc6e3oB7548cR//75scw5/XQ0cWN8bVE7u7umV7p7ekVZYu+bW1E9fJPqhL5zT95p9WTbksYNh+k+tntrJbVM0EIoztmmmXRpuHu/jRh/ToUGZUUkkH7kZg4OCNF5AUpGnkTWmZI/1kgtVhWxpoSlatB6dRwVhaw2SVlk54qls8Qra33l8f+9bPzUJfrsquHM58OEW3Nm017QPfe1o4OilV5+ti684i8lPPkp7l5AmFu8FqykOcGrydGdMXdaD6Iws5yLdIqsJeEOizo3WccwxUsGg89i7HgdIUQUgL3rFoFcFgyeVEiWn1EaqDHZPhncdRu/HvyP896fWWhDYMvyNb00+8BgBWHjmbB35zfcZNzREua1MKknsN/AhrtWiHUdFu4w5qil14SkikU5Hp5My+NIoLBWhkTTGCpkkifqs5BxeTKgDlJFqlaUCA294N5X99vv45I9/Z7XFrA3A+L/cMv6Xb00/bo7UPva5c/p23psVlSpJpZYPNMnNM9cpFbzPII+GZhmtox2vSiJinvOPhsfEYkTD6VD3Dc2JSIHumtZShn1K9ZW7UX7bYXMn7b/qmbjZaNDWrt9az8sJPhph0S/O0qVXnUXH3H/QlghVMfkG7OPsAs2mEkTn8HGTzuBSItiC5qM2iCyPKTGYqYhTV/TVOEBOxCDqGBlyDPZuTvuBn2T6+74s/Oc9LbjTWmseFr33WAFYcs5xuuJXlzJ+2TLK7YZqaD3EqOIlaBdJgTqRoZ5A5swqvd4E/kWm9Kt1ZTX19ayPatbqqJhYTbBqKWFIR2os1XEMHfAJ/vr9+TN73vflDR3uHLWBGn+6KTnClKPOlNrBHziuf993siL1mEoKicHZ4ATWucAT0oLCCIHSk2i9a6AR7WdqwDEAFh9iiiAyFZ7Mq8FYwVRrDHgY3nFvSnu8/cqpB3/xA6sx/EuBj7d2/ZYDrMm15ScvOAs4a+lVZ9+/9KcX79h1/1/oKENaLuHUY7zWEzbUWceJMcGqfZ1eV68aZ7mfTMNCC3kim1B2nuUjNVbO2JKu932ame85XjjjlhbceflXssnCooOP2Qlg0aXf1IWXX0j3k4/T3gXOGFw+8NsGsetsVkEx669RusLE+a/qsyoveAkN6okRTLXCcrFU9j+MCWf+X8ek9xy/Oriz8wZg/CduRFh/k3WAbPUe+h8y9cd3dAy+/1CW6jh0wJNkc419KBF4n2ZcIPJ8aXhAJkMem5ElVOBKzmC0yrDCyC57YHd/+2XT33/SoZxwyXO9nvtau/46Xa2KdLiuI4DMn/P5ry+7954vc+uNTPGgXe2M4BA1JJkUNT6w7PIAwYfxQSKCkQSrKelQyorNt6L8gaOZdvDnBP64oX9AG2t2J2mZf31tPvvbJwEnLfzRObrokjPpeOwROlKohO0+DibIuNZamE0R1A1hsMqKimX43Ycz/UdPyKSDP7ehG876nt059SU69sEtsx+9pn/wKPmn6x4Rnf1lnuzqZnnfCmTxd47U0o/OJWmLKswYsKFHQKopK1JId9sLff2+58w87Gsvqp1vfcL/rZx+awHcc+he7yq/+d2zZfHpR6n8+Bw62oUURUyJNhxuxLNyxuaYjxzLlA9+/qWN3FkPHKBl+K011jLOe6wDpIRoAitqrKhYVr7rI1x/9fxJL9X414N1XMv4W2u1wZIagw55Rmoplde/mfJ+//Lz3o+euFHgyO6e3rM2tNe8BoqFDZNSXuoJvDFvIIlRR6XPs3K7zeg8/HNs/qHjhIv/0NoaXt71UivldwCvbX2Mz8MBliedmAMPYurXz3vB85Vaa71dz7Y+gufpANt9IUqJf+/nrU+jtTbq1Xfr4bPL3dufU138GHbidkx47eelVTBprY16Dfz9nCd16d+20Kfvxtx/JaYyQJfCkLaz8r5zteUArbVRrWV3nHhaWTpO8M/chV/yV8yNx1Oq1UgEsBZMglqlMx2huvSBVsm8tTbs9eTl7DZ153PvHnnqdvzC+zC3n4nxw5QMJEmYGeZtOfR8+TBRTzxIGprjWw7QWhvcWvnn067SlX0H+Wfugv4Hcb8/ko6UMD8Cg0RVZp+aOFXG12V94gwLdSne+ZYDbKRro8rmPXoKr5px0Ll/r827HRbdg7vlFGw6TEc+LdQisbPXqWKDcFCcGSC5ZGeuUmgEnwYhk5YDtNZ6u5bfdorW5t6IXfIQ/poj6YgwngTUWIwGJrPP1J9VMZI1MkpBzzkIZeXVPFE0DUoRLQfYOFfHhv4G+q45Rv11X6W7DcSCsQaxNsib1DyIC2zl2MTlckGsqF+lkg/IEJGi2GGg+6cgPsG0bGWjXBvExjb3XHZe9tdzFy2+cq93jcJwc39Hd0kgKQeRZa9o6pE4VpUQzuKjypt4ECdB9bnhFobpSaYIHSfLh7ZgaTlAywHW/Rr824U68PP36iSdfJ/97exp/pmnrn7wB9vu3fAGjFJDwaeUnAEveOeDMLMHcSYcAjUNEY8DcRr1QYORh3+DU6gDdVnzF7kaYgsCbZxrvWuJXHrNez+UlGZeoY9fD7/5FB04JDHYksFVFzB9x2+fCcfuWgzjJQWLJSXNR20pmu/e6rORRybekylA12OCIKDbyOUTJY8dWg6wca7+9eWFrLhvztP62I0z9dE/YIeepT2BWJUK00Z9SptzY0x78Zh8eLaJo5GkIH5LVCTPJhhF1UKta3eqzyWfKUwMCw+pgdZaJ8DGum57Of/4NdDz5lu/2icP/wpz7bHYaopNEkzSFgRqaxrmSqsL2rIuRTHjG58lDlv0Po48pW740aSDakmU8M8UDX2Q9Sk6Q2E6V/wi6FyJthxgvVzdPb0y0L/oEMKgi5RV5/Xb433DdeDASHdPb7MW5RbA/sDcJoiUFJ47+7cHGFf4+hfP93U/cdZO+3dvs9f1/tFfIDedQgegJYMvJUGu3GssSQnifG6M6kBp5KWJU4wDH2X3jW92gGz4XVAzjBO3c2VDzTNCBc3y/HfD3zS+5QDrsxNcuQafaz5wydp6rcv/77u31v5xy5728Ztov/1cbAKmZEKs6QTjPWoUJ4KLqiPWm4IDeFBtcHLxMWMjUYfWNykYahyClE+FzHBP6Gv3Koya81XEWWkYudpygNZ68YHtnWfdq/dfvYte8wU6fUqbBdoSUgWXuqA3FXRqMS5UYW0mrqwZdNeQzXHaMNZJUx/OJIk5y2zgnc/0Ck0UvM20bWM8QKZoGCvBWk905hOLMgjkWkFwa72YwPbP5w8N3/mjjuRnx9OBR9oENYZUFdI0ThoKFASjddwdYEgMRfNpoxJSmc1RcEx3ZtMcc3HmbMid2OAMmaSnJ84zqos6G6Q+s6I5GHCgvkWGa60XYvh3XaCVOy/D/fBTTEBJSoIXEyawq0eMidNA4y6tBRQiTbuw1v1Cg5EnjSdAPBnEkM9JzSbDSzHVGe/SLNdDLuFfzwjFTJCv3+9SSNNWDNBaz8fwbz+vb+SOS3v8/x5BtwFpD/urd4FpaSPfUnx9OEvD3CGRwqTEJkAe49fmZdSG4pYUjFizHV7QeIRIPn1UGvL9udZzJvPmfRyRGmCXpuE0aTlAa61yDf5pztOVO/93pl55JBNVsW0Ghw0wJ86J9tG+M7iRFZ60GIBm/ByROmyR+sgtSUHVNPGXIqtNfX3InRZhjOKj3LnEGKDudpo7i+LrY35DdSG4hNZwtqvlAK01Rlbn1u9cX7v31/tXfnQcnVqj1G5RJMqLu4huJBaasv08nxqHNuz0AX54BJs5gMvGbkWiWgrGN6ZBNRv4peS5/SKEsi5woV2cSGpUY20gcISyg8Pm43wd3nuSqiNVWNbVje0wZ7QcoLXy9fTPjzmh/dl5p/mffInutALtgjclXBoiUsnxvAnYPaYi69i6uOuT52LqbqD5bh6m8cbB1l5BGh3ApR5NKThBY1ErJfCDjMSGlyjhj3rEO7wLxeMRBWcA247rmomf1YvM2IJxO73htol7f+ZzLQdoLR48lt16dv3I3fb359FZrWHbQdsSbM1jSOszorOZcdkJ0LDLFxyhgMZFi/g/Tm3PZkgjeImFLPUN/CVfc/lMX4my/QHRxyEuLkVqsbDsg4R/mkC1bTxMmILp6UWmbIabtj1m8pb9Aw/ccNw2R1/5g1ALvB34MdCKATbpdSv0vOLHx/Xp7f9L122XUy4Hw8cpxqcoSRyHpQ27b3OBSfIh6xktIRipYOoD1DMcHgNRH3du1CJYgikXHCBVxCneOiRVpBYMPZWQCHLlTpjSi5u0OXbSZuiMrTG9W9Wq8+48ftYHL5oDjz+vz6DlAJvoWnb9d3T4hvNo/+2ZtJdA2wzOKyZNAz9HBREfJwM1sykzA48ZGxp3eBUJN7JsqOangahiAC8WgyVRR03Bp66BwCe7vpP+my6hXG4nndyDjJ9J0rM5btoOyLjJD7unbj99yieuuOT5Gvqq1jrRfHw51aFbwriNq++Gsx9Kb7l8h44n7iBJgCRBtF5tFYkBZHEmtGhjnUobDac4Tjf03RpUQrrRxOfSGKiK96hTsni6mkL/Vq9ii288MOo6Lf3J7M/4Z//2yNSjb73mpb7vV0P7TdedeHK5e7MTav1P0zZlm9q41x9RbjnAJrIWXXH4bBYuPaf056tp9w7TVsJjKfkKvlQISvM4VxqIZw0xrhYGJkpUWZCwu+f0BhzqFRcRlE8j267UBV09jEydRTJxJq53e/oeumTPV3xl0RoZN/SXz/OqWQecfFxbx5QjaosfRZc+TmnFfHTwWRhejnGDWK+s9G0kB53Z33KATWAt/vEJ6m64kPEDfdj2Es4IxqcY8XgRRE2DhUs0bB/znDnUEQsS2k9Cvj/k871PcS4augsxblq2VMdNQSZNx/Zshpm6PbLZdlSfffTL5bd88fSXqkP75Nm9e4x/zQmnaW1kT+17Eumbix94Blm5hHJ1GaY2Ekh3CjbDaTbBi2CspTZYYWT3T7cg0Ma8Bm4+X0d+PYf2effRWQJfMqQqgZiG5uA9M3sfg1YbI1WPINZiCalFdT6fuJ56SC1okpB2TsBPnAGTZyFTt8D2boemwxdf/46vnHgILH4p7+Gp83fav/NVn/iqGRnY0y99El3yBHbgGdzwUmxlgFKtAgJGIDGBIiTGoGrQLGcUT6ZAJBKMgXQkZWSPo1sOsDGuJy889IiOxQvPb/vrdXQZsKUSNVJSAfEm2oHHSExYio2G4PD4yM4MRu4BY8CVS1TH9ZD0TIOpW2Kmb4tM3Y7K4nmn3nb8t+cc9AzPvJTXfCiM+8+rv3xqqWPicekzjyJLH8EPPEN5cCGlynLUKUnU8Ukk/i9SLFJTQtVj1NWzSCoNVKSssAyCEaFWrVHZ8+hWFmijgzvfP1LdH66ga8VySh0lnDhSTYnzEEkQBIfXEIg6pzj1YUdXMOUSOiHs6Dp5G5IpW2FmbI8fXHTOQwd/7Rt78ewzcP+LT71uxtSdzvvs7GTyDienix6l9vRf8X3zsCv7MNd+h6Q2QptAYsGWwo6OEdQkRNBFjSydGrtafC3GH1rIVJlGbpBkB54iRmLDvLZOgI0nrTnn6cpv58xse+p+2ssGYy3WObxCSuDWpxCwukBaLuG6JpFMnIZM3Rrp3Qqmbo1l6Io73v6lzx3AS9vRL4Rx77/6s58tTd3+5LTvcVj0KOmzT5AMPIUZXoZUKxgF64OkoZhYsRWDERtV3WLAjdaZnWG2O0YFxYFo5PjQ6ABjCJ5k9xpjqFVrjOx91MbvAMCZ3T29x2+shv/Mt7bd2yY73tx299V0ekUsOA9OwJchLbdT6+qBiTOhZwtkypbYma/CDTxxxrcO+ubJZ8DKl/warv381zu7/+nLbvGj+IWP4vvmwcCzlAeXU9aViA8btQgYGwJSb5Kg3xlpzMb7wOUhqzM0GbCYXN8zY/4XjbrR/MmbY0a7QIBAabXGyD6bhgNka8funt4HNibjX/iD47V27Xl0Do7AxHFUx03H9Ewn6d0KO2s7dOKUeSPP/Pn0GR++aM7a+Pvzrjx8tnvwgXMmLvgzplZBBEpJkC8UE+AWIjhjUC+5gWcmDIr3dVqzFHdpbXSAJnE3Vrm9r84JJBJRBdJaSnW/TcsBNipI9Jfzjta2xfOY8k+vIemZeMf82y86aZdT7v/9GjlVrvzwYZ2b732Oe/bhjnTRQ6TLn6G03f5Mfc+3Gz67p097s45/4A90jI+kNisBa2u98yvnv+WGnOWcYi9v5PWrb7bVJgdosvDRMipN/qCjPSZjqhqBtFZj+C2zNz0HiOu+7p7eXTf5gPnKw2e3b/bGc/ziudT6HkeXPoqseAYztAxTG8I6aLOQlKE/BX3/eSumvOlT3fkJ9MVX6oT+h6FUwvoaXhX1Ie+uNrKBvIncoLF3Z834/s0G39QDj7wwB8haiRseISZ3hNRVGH7rUZtsFmiX6JQHdvf0XrPRG/oPD/lY+1b7n6+L/9GRLpmHXzwPWfE0ye1XIJWLKCmUJaQ7jQm2KNYgpZB58QY6XZWhwcUN2j2mVEJSjxgXDTk0pzgjeaFMcHmzStFU68Q6g/qQsZcocNWA17O0phv7vRVpGA2RQN4QUw+kVbI6gKBOMFre5NOgVw/0L9poYNHTF773Q+Ne9fbzq0vmjneLHqe0dD46sAC58zf4P/yMRH3g/2jA6kZAywYVU284yTCLzyaqBNFZV9PYxFLcpYMWp3ofO67iTh57giVjhxZ3/IwY5wtBbKRI53n7/H+FqMGv4k03tVoWcX/2t+qnSOH3fBDMbdUBChBtQ3GER7+67d5T9/nMZX75wi3SxXOx/U8iy5+GB24i/cuvKKehyJXYUB31Bnx7SC8aH1OG3uNi7txkRiqNVlXPq4SurWaNTU0d4gLc0ALQFxE0tGblvyPN2KWg1CBR4pyGWEHrEGkMB5Dm+FZ1VAwwCiZlGSSjQRoxTVsOMIYjXNDd0/vJ9eH13HMIO2/x8W+ckJQ7P5wuehgWz8X2zUOXPou/4lhKqaODkFo0MfOCMWjZohL4AT4SdEyYGoEj7tYa+3ZF891bTCOcKLY1ihsDaSt51Th7TgqxpxSew+eaPBlGbzLYJgP3Wd1WmpttKAQHWd6ofjrVodDYr1chzhgAV6m1HGCMdcRA/6IjgEndPb3rVGR22e/Ovj999pEd3dMPU1o6n3RFH/ZnJyOuRpuG3TxJQBIQm+CTUq6K7DK6cZph3SZ2ZxwP1GAYEaeraKi2Oo29ADQYkxGP9YTiQoNBJdEJTIEgWvwL9f7gXA8ohywy6iRocL5ReX5pfFym9lBHNKyK3a8ZuPIh1DZioAau1joBVrf61lV8sOjXZ6j7w2XYS4+hI9DpMTbYlyQWTZK8s0pF8XHnjZYVAkUpzMDKUowxx66E9CRFWZB8O45U6CgyixRPgGhsphi0NsGMTGs/F6CK+F+kPperuPWPAYHytCVjCNmOld4cc3dfzWXKXlfsRXYSGvNtSxz3ecOiBd09vZut6ede8otvXJ7++TcfNj/4LJMS8G1JoCGLx6Fh19U6AA6dVE2al5LrPtEgH05TalELolTGjMol+sKOnWP9zKgkU2GWUcYYprMUk/31vuHi942GXwhutfk1S1P+k6aTgTGzSTLGHIDcHYuS6VlMLUDS1nKA57lmRkc4rrun96yX+mQ/hxlv+t5HFrgfncx4V6PUZakKGBfBNIKYIPkRNGxMPTuT7el5+q/RgGSUE9RtKqcP+FEWkmvs0/B36sFwxvdvhuEap7BIc85dx9qFs+doLHM14nsZMybQsU27IJZrim+0oV1TvQsQLA0MI5fCcoS26dvd0XKAF7bOHOhfdOZLgUV9v/6OVn7137TfdDntHYJvS/DOkWRYNXJeipi9yHLJpx1KU1WVZtlBoSgTG1odm5xFmp1FGwbKSWG3zMcNFc8XibjaZ05a32W1Hp82iL9pniI1hQMh/gGtZ5OcUUwmeqUSY5VAkQj9xhZyGhyIuqB1GDVFUw0hS01ByoLadtLxPTCum0r3JDr2+lcmH3Tc7i0HWEdp06eu+vql5bt/83H7/c8x3kBbe4maOqxz1DPoBYPMcu5NFdAGY888oa7+2kAYoyEFqQ07Y/HxjZKCmlt+EY+LGwNna6b4IHkckL0cXUW2R5qyQvnr81HQ03sUwbpM0iqrI8Sh16kLRp6m1BSqUffHlEvIuMmk4ychE6eiPdOw02Zhpm2BWrlt6Im7L5519BWXwILwR7/7J6AVA6wJR/h9d0/vW5/rsWbctI9XFs6l3UNbyaDisF6LYLnhmNdm+kC220tTMCijIcuojIoUhKkanlIaUuzZ7q/NO31qogOMDYFUDaou370zarLRujNkTuapyyeaeCr5WI+wHkyUQ1HncR7SCL0cUGu3pF1T8N2TcD3TSaZtiZ2+DcmUWQwvfOTUuf/v23NCv8JDz/sathzgpa/9oyO8o7un97erDCLe9kkBWHjJMVq94SI6B4ewHUnBYJu1dwo4RIrTTVaHi2mQJcyyMiIyNpNSGoPl5lRirvIcek5GxQAuwo2g7qxR9lObMpz1iS2iio0qzdaHiY8+GniV0GLpyu1oZxd+/HTspF7c5BnYaTMxM7dj8NlHT/3dx789599Z8gw8skYu3rpygElA30buCNc+n7Tp9MPOlkWXH3PC8rmPndZ+328YB2ipjMdjIjFMxQdMLTanJeSwBJdDjTzobc6cZDu5agFEZDu+FvOhhTx5cWePuDoNxisWKil4V2t4nK260H8rJoS1GtssXZA1zBQhXBolQQ24xGA7u5HubnTCVJg8i2TKLHTaLKRz/MODT9571uQPfvPi7p7eEfj7Grs4f/0W22y+32c/Vure7uRK/xJKZX9x92u/cvg6Lf2vh6zQtbaeT3yw/PoLqiNX/Xepc94DtHcKPrF4VawG/k3NOozYumoDoPmg2+I23hQES50E1jA5MY8FXAxi487fFBl4H90qHgDGWIaWVakcfiq9//rl/MELTthXu/7vJqQNcFAT8BZIOvCd3bjxPcikqZR6t0Cmbo2duhmVob4rFt981pydzl4zMijZmgM9h9w8+7D2Sa8+QSrLpvmVT+NWLECHFpJUFkN1OVodwLoR1EOlfRKdbzpp3WaBMqPYFBwhvsczunt6P7eqx0x46xFlgKUXf0YHr7+YrsFllLqCEnMqBlsTjFG8NNdDzahUZ5ZezHB8Q35ojCqrRC2fPOCW+kCLRAT1HvVprC47VopQMsnPG2KDN+91Th+dR1lrKffOwEyZhZnQM3f4sbtOv/q4S6+czaL+F4LHn2vN/Q47T37TV46Qzs0+wYpnOmr985Ghp5HBRZiRxcifL0NrQyTqKQMmibTsSBXxhKF9iRhKaR/pskffKC+jgXwVOHkTORA6wpG+6rXoisNny2MLz7H3/Jbu1EG7wXmba+/kYWwh09KQX6eu59N4Omg99Sh5NIwxEoVqA05RF3L9RCWImoG0rR1pH8dw51TKB3yEaYd8eZ3by/Lbvq21B3+FXbEQP9KPrQ1g0mpos4zUkMQEeggG1Bq8mHhyZQkqQcXHxnjFWqUC1HZeRx1hLVj0/GHRshvPH1r20/M7Jvzjz3S1g9gyNVEgxXoThJ3UIz62jUugOeTTUEwMmkVj+7jiUwdew+Bpl6kpg7NAuYwbNxEzcRq+ZybJtFnItK2QnikLBv5x5ymbz770gjX5GXwL2g/78XsPKm++97GS6htlYD66bB4100HlyaveuPkXh+7MHjt47/lDIz85smO8BGl2ib0Kamwg8Um9xqFiwhQC42NPg1AcEBMEqcM8YUmEmnrSXT7NekP/3YQc4eHunt5XPNeDnvnR1zS9+hx6liyi3A7eWNCQWnQBcodSkMTRi87ja3XhKu9D4Jkq+HIJN34S2jMdM3k6TN0cO21zmDh1weAT93xjiyPXbM/widBz7C+On90x/ZWnuv7H0b75mGULYHAxyeCzmMqKoIaeKomBsoGVw7Dinz/M5oddkdvksuu/qOXrv0V5XAnv0gDtskDeFBIBErf7SGs1UU3dS6xpmLpQb2j2Ebx3VF/zmfUnDRqHQ38d+PJG7gA7RGffobund5W5vBkf/IrcegQ7J6//2H3yh6voGRyEdot3HlMLKcdaNqHFwEhicG3jMON7YNJ0/JQtkd6tsdO3Il02//RrP3baGYexaDHct0bexL0Hsf0W/+/ET5S6tz7WL1/UwfKncQPzYdl8WPEMcuu5iKvQoUH2JDucbLYjm6BIjTE4MXSRUlkxr6lOkSAqmJhOKgpdNfcY52OURHN2B6ZAq4gaptGL8E7jFPr1N4BswaK45l92zAnJo4+c5u6+iZJ6pHsyMnUL/PQtsdM3J5m2Bamm1y655jvfeMX/rLnsyvyvb7t3x2v+7RPlcRM+Xlv8BG7pPHTZ05iBJZiRAWy6giQdIjI4woDsJLZVEuvMkkTb9FGoME54jNQGRMLpVq2xYss3MeXoP+Wfx/Lfn6Tl675OW6cNhbaCwavRqECdhfsZF8jnRfS8L14kOJyJoliGAIFee/z6WQjbBLNFq3WEzT9+9unA6Y/MOeYEOzi0YtvPXzQHFgF3veS///u92H7Xz5/8WTt+6hEseRxd/BRm+Tx02WJkUR/ml6di0grtPnaYWUL1yIAaA6VSvierNYEhGsemhnaxWqB3x3pEMEoTlH3U5b0GYbjGaDErL2GWME4ag/x87nAhkxUzZPi6SpyYWOfwcZyTGCwaqs92Pe8JjrBoNnDOJuIIV3T39H5kVY/ZfvbZp7/I524f/uWpx7Vv/urT0sVPooueIF06H5Y+iRnqx//0VMSltEftTWPBlCJeNhKmxojEPoJCw4uCdz4En9kPMr5Rhr+lqRiXN+Fks3ujzfqoPl1YNs4Z8L5AnhtFHM1OhGKzjeaY3/qg02LVkKCkJiWtwVDXdJTKl9d7KkR3T+8cYM4mAos+PNC/6MPA+O6e3hes2PbQ51/39u7Xvn+2aS8d6J99ArNwLmbpfKqfezV2ZBCtDNLm6+oPYiFJorZ/OQnBtCje+Gh4WqAbx5FGUu8aUwmZqBCHZHN8s/pDHExtTaMwUHa/kThCyRfub+IqGVNXRszAU9EDCkGB+oJXBiYSFoMx4LVG1YErQTphM9zm+1KeOuv0CW887RsbDBdoU4JFwIoX0o32xH+8W8uP30XpsQcwD5wACpk9myRgc7VAW6QtxPShoqRGwpwAH2VNjIl8nqzCnPUOZzCjzlnOiW4iAeL4QHYLwx81p2zrKLJF3MqNYAivx0lo0m+AP97Xu8zy4RuN9I1Ghwj4XozBekXTGkNAbfwUZNs3YHZ4BxN2Plrgf/NfMRto0PiBTSFAHuhfpAP9i+5e3WMWnnec9tz2K6aufIbx6SDj2y3jxie0dZYwnSW0LcFZi6oFJ5jUR3KOi/1fitNwv3jBOMBZxNdv+YDq2Pgb0qxCqDSEHV5duMNkVTofk+8umwJpws1H6nRUZ87Zp9kgi6KPeB/5/XH6jNR5s8YHqkZwuiA6KgT6aDrsWKltDGy+G+7tX+WJxUt2mHDIr2X8zkeP2lA2SDZod0/vlYBsIqfBbqtjm9p5f6NtnCEtGdSF+VsqHjGmAZfnBbO8KuRzxGCKu6hks7yKbbtB7i0bjpd3Efh6B5YXn+t7CpFm4anPFDbFZvc421clMEklFDasb2wDMxoLHqkEbRcb7k9tFlSXEHEkLiUddtSMpTp1B8wOB+C62k6d/JZvnwT3rPbD3aDp0JsYLLp2oH/RvO6e3i2LP3ReQ20g6ytQjYZZn9Y4ljRhaDUOBSVp2nu96ugheFpswqERfkg9+NXc6bL7fAMXr57FyUaqSpQqGaOL0mm90cY5UBPUpWOLmlQrVBVW9mwDr34TyTa7z5v0hqO2hIef94e6UfQDxGzREcD5G7kTbDE6T1iLkCRKmFAfLh3zLZHebEYZYVZaKuJojc0pxXZJUQk7sUR5CGlE9XkjfaH/QIpYvzl7gza1ZjY5R+G15HOFTQIe0jSl4iHtGAfbvI5kp/fwpb2O7ziXuSNFbL9JOUB0gguACwb6Fz05pqFspEti6jDmXaiTX+oDrOs6mdqoqJZ1cGnkyOTPQaOKRDxR6sqIxYnwOQ2vQQirSbgC05S+9E1NPuH0sKPen7UCaQ23EkZKZdJZu1Da+UCqffd/ePrHfvFDuOklfX4bXUdYBhE2mWqymojjS6imdQ0fVYyJnH5vAxWBiMWtj8ZnEC84cZESXW9lRH1+ZojG6RY+NNNoNkuY4lxhH9o0RUL6U+Pol4Kj5d9IaGlXNWgcD1MCRkzTCVCrMLhCkc22g533wW6x/bVT9/v8v6wpOsdG6QBNsGgP4LaN2v7jrK+8fVHr4MQXGtJNhs+9FvqLs53bRAUGGVORymcOYaSeCi1s59lrECOILYy5UB27FVMDKy21HhGlRBig4bWt4WErvZ7c9vHvnjxln88IPLpWPr+Nuie4u6f3jzFb1Af0bJwQqNANph4nmu/QmcCDiOC91ifAS9DSzIJQMSEbo3GAXL3DUhscpT6uS+o5/uh32dfehenwFkhdDIaNGR2AC7QBpIobHmbZCJgtdgeuyx+32UFnnAKcsjY/v02iKb67p3fSxgqLjLUZ6TEGu1IYiZhZZz39GLZ0oihuyAI1iEr4oiCcaThJjBEKPTXFV1EPoGMYkilBhMSRxoDcRtayR2opqYPhznGkW2yPfd0HmfTWL6xzcuYmpQqxUWaLjMEoGC+kkYpgREMWSINMuSPsymR+4WnoKhOt43gRrWvgSvGfLHINTyLqczyf5f1D95rHkeDVYEjBxgHcCElaI01hOIF0+g6UdtoPmTbrisn7fukj8OeX5ePb5GRRCtmiAWD8hv5+fOpjbKp1eB+8ILdyyU4Gr0FBsLjlZ9keU0jHmCYI1BScZmzLUd2X8QuTTTgtGawqOpJSA1ZOnIbffg/adj1gxeTdP9X9QvL1LQdY847QvTHAIvWKz/T5C5Li6kPmRmLFtqipW2wmyWXNMyKbFCbDZHKKPsQGGtWejQgm5HFyefWM/WCMwYhHKim1Kgx2d6Hbvppkl7ex/B+X77P1kT+/BX6+3nx+m7wwVoRFmwPzNvBwOEu8FCy8sXIr9Yi1HtQSg1qvuUSnMRlVOeT+HQFSmfrxkuV/ArdfQHBY56lVoFoW3OY7IjsfwFC6/KhZH7toDtyxXn5qLWW44ATzY7bofmDHDcrsI0PMa0EGH4nZylgFNnVqhMm1guqCuRkLUyLv2DsNAW88GayE3R7jAwEu9ThrIDEkzuEHHRWBocmbwc77Ybbb9eapb//svmtS2KrlAOvGEXba0GCRRnlCybqgVOJOXlCAC3gmzMbC1OduxVhBs2nthoIYbzESzk4SS0kFbzxSq1IdhpXjO9GdX0+y27/whXd/Yfyl/GAl/GCDueYtB1g1LNoGeGy9d4CMMRmntRgIhp4R2kygLWtGkjPxhMjwvyt0Z6k0yO6rxgDYQgmgllKrwVAC6cxtSXZ9G/R0njzlfWecAjdvkNe65QCrdoK5ERZdDnx4ffYAnwKu3jYlRXH+mCEia0LPCllGGmeIFSUWQ5MjRsC4FKnAMDA0bXPk1fvQttPuCya9bfZmcO4Gf52lZerPb60vsKi5S2zBMW/R9jtuwLaXg26ojXBHQq9uls0xptA3LrFPFkGNDZLlgBWNeoIppuKo1KDS3Qbbvw5500FMOeizG529tE6AF2h461t84H2oA2S04fANcYc3eTan+KK9Vaw3KAafKzaASVPciGPYgt9yO+S1BzBUW/HJLY+79IKNlVLVcoAXFx98DLhs/UBAGng8sTIrWfti4XwPtYAAboxIGGuaGAxKUkupVZQqsGzqTJI9dqe0237zpv7LUVty8aMb/fVsOcCLc4IfAD8Y6F80BHS87FGwZI0sHuMl5+AUO769CfO1EINRhx92DDuoTpiA23V3OnZ/J+P3+UAU8f3ZJnMtWw7w0hyh8+WGRT4oP4UiV6axY0LGx8QKrrcmENCGPDXnGe60uB12pbTHOxmZf9OBW57022vgt8Axm9w1bDnAmoNF+wPXr3MHcIqmIOVYD9Ac9ACCqzqk6qkkUNl6e5I99sFNmXbmrI+dejwX3rPJX7uWA6w5J/g9IW36GLDNOvvDNY+kYaJMoCUYbDXFD8NKIJ0xldI/70P7Hm/pn3XQpybx00daF6uwWmnQtbTWFixqToPO/fgeOvGuOyh1wMiIo1qDak83vPr1dOz3XqZ/5OjWNV7NMq2PYO3BItYF3XpwhFqfY6CaMLDL7vCFb3Lj/w1su/VFv5eW8bdOgPXlNLgI+MTaOAEe+dKRWnYjpFvPOOOfPvnNz7U+7ZYDbNSw6IVMp2+tlgNsVI7QMv5WDLAxxQdHtYy/dQK0ToP+RU8DM1dx94ndPb2ntz6l1toUHOGxKId+fuvTaK3Waq3Waq3Waq3Waq3Waq3Waq3Waq3Waq3Waq3Waq01tP4/JTI29Wm7FnsAAAAASUVORK5CYII=") center / contain no-repeat; transition: transform .5s var(--ease-out); }

/* ---------- Botões ---------- */
.btn{
  display: inline-flex; align-items: center; justify-content: center; gap: 8px; min-height: 42px; padding: 0 20px;
  border-radius: 999px; border: 1px solid transparent; font-weight: 600; text-decoration: none; line-height: 1;
  transition: background .2s var(--ease), border-color .2s var(--ease), color .2s var(--ease), transform .25s var(--ease-out), box-shadow .25s var(--ease-out);
}
.btn-sm{ min-height: 38px; padding: 0 16px; font-size: .9rem; }
.btn-primary{ background: var(--accent); color: #fff; }
.btn-primary:hover{ background: var(--accent-deep); box-shadow: 0 12px 22px -12px rgba(232,89,12,.8); }
.btn-ghost{ background: var(--paper); border-color: rgba(17,18,20,.16); color: var(--ink); }
.btn-ghost:hover{ border-color: var(--ink); background: #fff; }
.btn:not([disabled]):hover{ transform: translateY(-1px); }
.btn:not([disabled]):active{ transform: translateY(1px); }
.btn[disabled]{ opacity: .45; cursor: not-allowed; }
.icon-btn{ display: inline-grid; place-items: center; width: 40px; height: 40px; background: transparent; border: 0; border-radius: 50%; color: inherit; transition: background .2s var(--ease), transform .3s var(--ease-out); }
.icon-btn:hover{ background: rgba(242,239,233,.14); transform: rotate(90deg); }

/* ---------- Estrutura ---------- */
.app{ display: grid; grid-template-columns: 268px minmax(0, 1fr); min-height: 100vh; min-height: 100dvh; }
.side{
  position: sticky; top: 0; height: 100vh; height: 100dvh; display: flex; flex-direction: column; gap: 30px; padding: 24px 16px;
  background: var(--ink); color: var(--stone); border-right: 1px solid var(--line); animation: slide-in .6s var(--ease-out) both;
}
@keyframes slide-in{ from{ opacity: 0; transform: translateX(-14px); } to{ opacity: 1; transform: none; } }
.brand{ display: flex; align-items: center; gap: 12px; padding: 0 10px; text-decoration: none; color: var(--stone); }
.brand:hover .brand-mark{ transform: scale(1.08) rotate(-4deg); }
.brand-text{ font-family: var(--font-display); font-weight: 800; font-size: 1.2rem; line-height: 1.1; letter-spacing: -.01em; font-stretch: 116%; }
.brand-text small{ display: block; margin-top: 3px; font-family: var(--font-body); font-weight: 400; font-size: .8rem; letter-spacing: 0; color: var(--cream-dim); font-stretch: 100%; }
.nav{ display: flex; flex-direction: column; gap: 4px; }
.nav a{
  position: relative; display: flex; align-items: center; gap: 12px; padding: 12px 14px; border-radius: 12px; text-decoration: none;
  color: var(--cream-dim); font-weight: 500;
  transition: background .2s var(--ease), color .2s var(--ease), transform .3s var(--ease-out);
}
.nav a:hover{ background: rgba(242,239,233,.07); color: var(--stone); transform: translateX(3px); }
.nav a[aria-current="page"]{ background: rgba(232,89,12,.16); color: #fff; }
.nav a[aria-current="page"] svg{ color: var(--accent-soft); }
.nav a[aria-current="page"]::before{ content: ""; position: absolute; left: -16px; top: 12px; bottom: 12px; width: 4px; background: var(--accent); border-radius: 0 4px 4px 0; }
.badge{ margin-left: auto; min-width: 22px; padding: 2px 8px; border-radius: 999px; background: var(--accent); color: #fff; font-size: .75rem; font-weight: 700; text-align: center; line-height: 1.5; animation: pulse 2.4s ease-out infinite; }
@keyframes pulse{ 0%,100%{ box-shadow: 0 0 0 0 rgba(232,89,12,.5); } 60%{ box-shadow: 0 0 0 7px rgba(232,89,12,0); } }
.side-foot{ margin-top: auto; display: flex; flex-direction: column; gap: 14px; padding: 18px 10px 0; border-top: 1px solid var(--line); }
.me{ display: flex; align-items: center; gap: 12px; min-width: 0; }
.avatar{ flex: none; display: grid; place-items: center; width: 40px; height: 40px; border-radius: 50%; background: var(--accent); color: #fff; font-family: var(--font-display); font-weight: 700; font-size: 1.1rem; box-shadow: 0 0 0 3px rgba(232,89,12,.28); }
.me-text{ min-width: 0; display: flex; flex-direction: column; }
.me-text strong{ font-size: .95rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.me-text small{ color: var(--cream-dim); font-size: .78rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.side-actions{ display: flex; gap: 8px; }
.side-link{ flex: 1; padding: 9px 10px; text-align: center; text-decoration: none; font-size: .86rem; font-weight: 500; color: var(--stone); background: transparent; border: 1px solid var(--line); border-radius: 999px; transition: border-color .2s var(--ease), background .2s var(--ease); }
.side-link:hover{ border-color: var(--accent); background: rgba(232,89,12,.12); }
.content{ min-width: 0; }
.top{ display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 28px clamp(18px, 3vw, 40px) 10px; }
.top h1{ font-size: clamp(1.7rem, 3vw, 2.3rem); letter-spacing: -.03em; }
.top h1:focus{ outline: none; }
#refreshBtn svg{ transition: transform .6s var(--ease-out); }
#refreshBtn:hover svg{ transform: rotate(180deg); }
#main{ padding: 14px clamp(18px, 3vw, 40px) 64px; max-width: 1320px; }
#main:focus{ outline: none; }
.view:not([hidden]){ animation: rise .55s var(--ease-out) both; }
@keyframes rise{ from{ opacity: 0; transform: translateY(16px); } to{ opacity: 1; transform: none; } }

/* ---------- Faixa de resumo ---------- */
.band{
  position: relative; overflow: hidden; margin-bottom: 26px; padding: 32px 34px 26px; border-radius: 24px;
  background-color: var(--ink); color: var(--stone);
  background-image:
    radial-gradient(circle at 92% 0%, rgba(232,89,12,.4), transparent 45%),
    linear-gradient(rgba(242,239,233,.05) 1px, transparent 1px),
    linear-gradient(90deg, rgba(242,239,233,.05) 1px, transparent 1px);
  background-size: auto, 48px 48px, 48px 48px;
  box-shadow: 0 30px 50px -34px rgba(17,18,20,.7);
}
.band-head h2{ font-size: 1.6rem; letter-spacing: -.03em; }
.band-sub{ margin-top: 4px; color: var(--cream-dim); font-size: .9rem; }
.kpis{ position: relative; z-index: 1; display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); margin: 26px 0 0; border-top: 1px solid var(--line); }
.kpi{ padding: 20px 20px 4px 0; min-width: 0; animation: rise .7s var(--ease-out) both; }
.kpi:nth-child(1){ animation-delay: .08s; } .kpi:nth-child(2){ animation-delay: .16s; } .kpi:nth-child(3){ animation-delay: .24s; } .kpi:nth-child(4){ animation-delay: .32s; }
.kpi + .kpi{ padding-left: 24px; border-left: 1px solid var(--line); }
.kpi dt{ color: var(--cream-dim); font-size: .88rem; }
.kpi dd{ margin: 6px 0 3px; font-family: var(--font-display); font-weight: 700; font-size: clamp(1.8rem, 2.8vw, 2.5rem); letter-spacing: -.03em; line-height: 1.05; overflow-wrap: anywhere; font-stretch: 112%; }
.kpi small{ display: block; min-height: 1.2em; color: var(--cream-dim); font-size: .84rem; }

/* ---------- Painéis ---------- */
.panel{
  margin-bottom: 26px; padding: 24px 26px; background: var(--paper);
  border: 1px solid var(--line-dark); border-radius: var(--radius-lg); box-shadow: var(--shadow-card);
  transition: box-shadow .35s var(--ease-out), border-color .25s var(--ease);
}
.panel:hover{ border-color: rgba(232,89,12,.3); }
.panel-flush{ padding: 0; overflow: hidden; }
.panel-head{ display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-bottom: 16px; }
.panel-head h2{ font-size: 1.15rem; }
.panel-link{ font-size: .9rem; font-weight: 600; color: var(--accent-deep); text-decoration: none; }
.panel-link:hover{ text-decoration: underline; text-underline-offset: 3px; }
.duo{ display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 26px; }
.duo .panel{ margin-bottom: 0; }
#view-visao-geral > .panel{ margin-top: 0; }
.legend{ display: flex; gap: 16px; margin: 0; padding: 0; list-style: none; font-size: .88rem; color: var(--muted); }
.legend li{ display: flex; align-items: center; gap: 7px; }
.sw{ width: 12px; height: 12px; border-radius: 4px; }
.sw-orders, .bar-orders{ background: var(--accent); fill: var(--accent); }
.sw-quotes, .bar-quotes{ background: var(--ink); fill: var(--ink); }
.chart svg{ display: block; width: 100%; height: auto; }
.chart .grid{ stroke: rgba(17,18,20,.08); stroke-width: 1; }
.chart text{ font-family: var(--font-body); font-size: 11px; fill: var(--muted); }
.chart .chart-note{ margin-top: 8px; color: var(--muted); font-size: .9rem; }

/* ---------- Listas curtas ---------- */
.mini{ margin: 0; padding: 0; list-style: none; }
.mini li + li{ border-top: 1px solid var(--line-dark); }
.mini-item{ display: flex; align-items: center; justify-content: space-between; gap: 12px; width: 100%; padding: 13px 10px; background: transparent; border: 0; text-align: left; border-radius: var(--radius); transition: background .2s var(--ease), transform .3s var(--ease-out); }
.mini-item:hover{ background: rgba(232,89,12,.07); transform: translateX(3px); }
.mini-main{ min-width: 0; display: flex; flex-direction: column; }
.mini-main strong{ overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.mini-main small{ color: var(--muted); font-size: .84rem; }
.mini-side{ display: flex; flex-direction: column; align-items: flex-end; gap: 4px; flex: none; }
.mini-side b{ font-family: var(--font-display); font-weight: 700; }

/* ---------- Barra de ferramentas ---------- */
.toolbar{ display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 20px; }
.search{ position: relative; flex: 1 1 280px; min-width: 0; }
.search svg{ position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--muted); pointer-events: none; }
.search input{ width: 100%; height: 46px; padding: 0 18px 0 44px; background: var(--paper); border: 1px solid rgba(17,18,20,.14); border-radius: 999px; transition: border-color .2s var(--ease), box-shadow .25s var(--ease); }
.search input::placeholder{ color: #8a847a; }
.search input:focus, .select:focus{ outline: none; border-color: var(--accent); box-shadow: 0 0 0 4px rgba(232,89,12,.16); }
.select{
  height: 46px; padding: 0 40px 0 18px; background-color: var(--paper); border: 1px solid rgba(17,18,20,.14); border-radius: 999px;
  appearance: none; -webkit-appearance: none; transition: border-color .2s var(--ease), box-shadow .25s var(--ease);
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1.5 6 6.5l5-5' fill='none' stroke='%236B665E' stroke-width='2'/%3E%3C/svg%3E");
  background-repeat: no-repeat; background-position: right 16px center;
}
.toolbar .btn{ min-height: 46px; }

/* ---------- Tabelas ---------- */
.tbl-wrap{ overflow-x: auto; }
.tbl{ width: 100%; border-collapse: collapse; min-width: 760px; }
.tbl th{ padding: 14px 18px; text-align: left; white-space: nowrap; background: var(--ink); color: var(--stone); font-size: .82rem; font-weight: 600; }
.tbl td{ padding: 15px 18px; border-bottom: 1px solid var(--line-dark); vertical-align: middle; font-size: .94rem; }
.tbl tbody tr{ transition: background .2s var(--ease); }
.tbl tbody tr:hover{ background: rgba(232,89,12,.05); }
.tbl tbody tr:last-child td{ border-bottom: 0; }
.tbl .num{ text-align: right; font-variant-numeric: tabular-nums; }
.tbl th.num{ text-align: right; }
.tbl .money{ font-weight: 600; white-space: nowrap; }
.stack{ display: flex; flex-direction: column; min-width: 0; }
.stack small, .sub{ color: var(--muted); font-size: .82rem; overflow-wrap: anywhere; }
.clamp{ display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; max-width: 360px; overflow-wrap: anywhere; }
.link-btn{ padding: 0; background: none; border: 0; text-align: left; font-weight: 700; color: var(--accent-deep); text-decoration: underline; text-decoration-color: transparent; text-underline-offset: 3px; transition: text-decoration-color .2s var(--ease); }
.link-btn:hover{ text-decoration-color: currentColor; }
.wa{ color: inherit; text-decoration: none; white-space: nowrap; border-bottom: 1px dotted var(--muted); }
.wa:hover{ color: var(--accent-deep); border-bottom-color: var(--accent-deep); }
.tag-admin{ display: inline-block; margin-left: 8px; padding: 2px 10px; border-radius: 999px; vertical-align: middle; background: var(--ink); color: var(--stone); font-size: .72rem; font-weight: 600; }
.state{ padding: 56px 24px; text-align: center; color: var(--muted); }
.state strong{ display: block; margin-bottom: 4px; color: var(--ink); font-family: var(--font-display); font-size: 1.1rem; font-weight: 700; }
.state .btn{ margin-top: 16px; }
.state-small{ padding: 18px 6px; text-align: left; }
.pager{ display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; padding: 14px 18px; border-top: 1px solid var(--line-dark); font-size: .9rem; color: var(--muted); }
.pager:empty{ display: none; }
.pager-btns{ display: flex; align-items: center; gap: 12px; }

/* ---------- Status ---------- */
.pill, .status-select{ display: inline-block; padding: 5px 13px; border: 1px solid transparent; border-radius: 999px; font-size: .8rem; font-weight: 600; line-height: 1.3; white-space: nowrap; }
.status-select{
  padding-right: 30px; cursor: pointer; appearance: none; -webkit-appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='7' viewBox='0 0 10 7'%3E%3Cpath d='M1 1.2 5 5.2l4-4' fill='none' stroke='%23111214' stroke-opacity='.65' stroke-width='1.8'/%3E%3C/svg%3E");
  background-repeat: no-repeat; background-position: right 11px center; transition: box-shadow .2s var(--ease);
}
.status-select:hover{ box-shadow: 0 0 0 3px rgba(17,18,20,.07); }
.status-select:disabled{ opacity: .6; cursor: progress; }
.status-select option{ background: #fff; color: var(--ink); }
.tone-new{ background-color: rgba(232,89,12,.13); color: #9A3B00; border-color: rgba(232,89,12,.35); }
.tone-progress{ background-color: rgba(217,164,0,.16); color: #6B5000; border-color: rgba(184,134,0,.4); }
.tone-ok{ background-color: rgba(46,125,79,.13); color: #1F6340; border-color: rgba(46,125,79,.36); }
.tone-off{ background-color: rgba(107,102,94,.12); color: var(--muted); border-color: rgba(107,102,94,.3); }

/* ---------- Gaveta de detalhes ---------- */
.drawer{
  position: fixed; inset: 0 0 0 auto; margin: 0 0 0 auto; padding: 0; border: 0; width: min(560px, 100%); max-width: 100%;
  height: 100vh; height: 100dvh; max-height: none; background: var(--paper); color: var(--ink); box-shadow: var(--shadow-pop);
}
.drawer[open]{ display: flex; flex-direction: column; animation: drawer-in .45s var(--ease-out); }
.drawer::backdrop{ background: rgba(17,18,20,.55); -webkit-backdrop-filter: blur(4px); backdrop-filter: blur(4px); }
@keyframes drawer-in{ from{ transform: translateX(40px); opacity: 0; } to{ transform: none; opacity: 1; } }
.drawer-head{ display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 16px 16px 16px 26px; background: var(--ink); color: var(--stone); flex: none; }
.drawer-head h2{ font-size: 1.35rem; }
.drawer-body{ flex: 1; overflow-y: auto; padding: 26px; }
.d-section{ margin-bottom: 28px; }
.d-section h3{ margin-bottom: 14px; padding-bottom: 10px; border-bottom: 1px solid var(--line-dark); font-size: 1.02rem; }
.kv{ display: grid; grid-template-columns: 120px minmax(0, 1fr); gap: 10px 14px; margin: 0; }
.kv dt{ color: var(--muted); font-size: .9rem; }
.kv dd{ margin: 0; overflow-wrap: anywhere; }
.msg{ margin: 0; padding: 16px 18px; white-space: pre-wrap; overflow-wrap: anywhere; font-family: var(--font-body); font-size: .95rem; background: #fff; border: 1px solid var(--line-dark); border-left: 3px solid var(--accent); border-radius: var(--radius); }
.items{ width: 100%; border-collapse: collapse; font-size: .9rem; }
.items th{ padding: 9px 12px; text-align: left; background: var(--sand); font-size: .8rem; font-weight: 600; }
.items td{ padding: 11px 12px; border-bottom: 1px solid var(--line-dark); vertical-align: top; }
.items .num{ text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
.items-wrap{ overflow-x: auto; background: #fff; border: 1px solid var(--line-dark); border-radius: var(--radius); }
.total-row{ display: flex; align-items: baseline; justify-content: space-between; gap: 12px; margin-top: 16px; padding-top: 16px; border-top: 2px solid var(--ink); }
.total-row strong{ font-family: var(--font-display); font-size: 1.8rem; letter-spacing: -.03em; color: var(--accent-deep); }

/* ---------- Aviso ---------- */
.toast{
  position: fixed; left: 50%; bottom: 26px; z-index: 200; max-width: min(92vw, 460px); padding: 14px 20px;
  background: var(--ink); color: var(--stone); border-left: 4px solid var(--accent); border-radius: 14px; box-shadow: var(--shadow-pop);
  font-size: .94rem; opacity: 0; pointer-events: none; transform: translate(-50%, 14px);
  transition: opacity .3s var(--ease), transform .45s var(--ease-out);
}
.toast.show{ opacity: 1; transform: translate(-50%, 0); }
.toast.error{ border-left-color: #E04B4B; }
.noscript{ padding: 24px; text-align: center; }

/* ---------- Acesso negado ---------- */
.denied{ display: grid; place-items: center; min-height: 100vh; min-height: 100dvh; padding: 24px; background: var(--ink); color: var(--stone); }
.denied-box{ max-width: 440px; animation: rise .7s var(--ease-out) both; }
.denied-box h1{ margin: 24px 0 10px; font-size: 2.1rem; }
.denied-box p{ margin-bottom: 26px; color: var(--cream-dim); }

/* ---------- Responsivo ---------- */
@media (max-width: 1000px){
  .app{ grid-template-columns: 1fr; }
  .side{ position: static; height: auto; flex-direction: row; flex-wrap: wrap; align-items: center; gap: 10px 16px; padding: 14px 16px; border-right: 0; border-bottom: 1px solid var(--line); animation: none; }
  .nav{ order: 3; flex-direction: row; width: 100%; overflow-x: auto; padding-bottom: 2px; }
  .nav a{ flex: none; padding: 10px 14px; }
  .nav a:hover{ transform: none; }
  .nav a[aria-current="page"]::before{ left: 14px; right: 14px; top: auto; bottom: 0; width: auto; height: 3px; border-radius: 3px 3px 0 0; }
  .side-foot{ margin: 0 0 0 auto; padding: 0; border: 0; flex-direction: row; align-items: center; }
  .me-text{ display: none; }
  .kpis{ grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .kpi:nth-child(3){ padding-left: 0; border-left: 0; }
  .kpi:nth-child(n+3){ border-top: 1px solid var(--line); margin-top: 14px; }
  .duo{ grid-template-columns: 1fr; }
}
@media (max-width: 760px){
  .tbl{ min-width: 0; }
  .tbl thead{ display: none; }
  .tbl, .tbl tbody{ display: block; }
  .tbl tr{ display: block; padding: 14px 18px; border-bottom: 1px solid var(--line-dark); }
  .tbl tbody tr:last-child{ border-bottom: 0; }
  .tbl td{ display: flex; justify-content: space-between; align-items: center; gap: 16px; padding: 6px 0; border: 0; text-align: right; }
  .tbl td::before{ content: attr(data-label); flex: none; color: var(--muted); font-size: .82rem; text-align: left; }
  .tbl td .stack{ align-items: flex-end; }
  .tbl .num, .tbl th.num{ text-align: right; }
  .clamp{ max-width: 100%; }
  .toolbar .btn{ flex: 1 1 100%; }
  .select{ flex: 1 1 100%; }
}
@media (max-width: 480px){
  .band{ padding: 24px 20px 20px; border-radius: 20px; }
  .kpis{ grid-template-columns: 1fr; }
  .kpi, .kpi + .kpi{ padding: 14px 0 4px; border-left: 0; }
  .kpi + .kpi{ border-top: 1px solid var(--line); margin-top: 8px; }
  .kpi:nth-child(n+3){ margin-top: 8px; }
  .kv{ grid-template-columns: 1fr; gap: 2px 0; }
  .kv dd{ margin-bottom: 10px; }
}
@media (prefers-reduced-motion: reduce){
  *, *::before, *::after{ animation: none !important; transition-duration: .001ms !important; }
}

</style>
</head>
<body class="denied">
  <main class="denied-box">
    <span class="brand-mark" aria-hidden="true"></span>
    <h1>Acesso restrito</h1>
    <p>Esta área é exclusiva da administração da loja. A conta com a qual você entrou não tem permissão.</p>
    <a class="btn btn-primary" href="index.html">Voltar ao site</a>
  </main>
</body>
</html>
<?php
    exit;
}

$csrf = admin_csrf();
$nome = (string)$admin['nome'];
$inicial = mb_strtoupper(mb_substr($nome, 0, 1, 'UTF-8'), 'UTF-8');
?><!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<meta name="csrf-token" content="<?= h($csrf) ?>">
<title>Painel admin — ConstrutoMAX</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Archivo:wdth,wght@62..125,400..900&amp;family=Instrument+Sans:wght@400;500;600;700&amp;display=swap" rel="stylesheet">
<style>
/* =========================================================
   PAINEL ADMIN — ConstrutoMAX (tema premium)
   Mesma linguagem visual da loja: Archivo + Instrument Sans
========================================================= */
:root{
  --ink:        #111214;
  --ink-2:      #191B1E;
  --stone:      #F2EFE9;
  --paper:      #FBFAF7;
  --sand:       #E5E0D6;
  --muted:      #6B665E;
  --accent:     #E8590C;
  --accent-deep:#C2480A;
  --accent-soft:#FF8A4C;
  --danger:     #D64545;

  /* nomes antigos usados no resto do painel */
  --black: var(--ink);
  --black-soft: var(--ink-2);
  --cream: var(--stone);
  --cream-dim: rgba(242,239,233,.62);
  --orange: var(--accent);
  --orange-dim: var(--accent-deep);
  --orange-light: var(--accent-soft);
  --steel: var(--muted);

  --line: rgba(242,239,233,.12);
  --line-dark: rgba(17,18,20,.09);

  --font-display: 'Archivo', 'Arial Narrow', sans-serif;
  --font-body:    'Instrument Sans', system-ui, sans-serif;

  --radius: 10px;
  --radius-lg: 18px;
  --ease: cubic-bezier(.4,0,.2,1);
  --ease-out: cubic-bezier(.22,1,.36,1);
  --shadow-card: 0 1px 2px rgba(17,18,20,.05), 0 10px 28px -16px rgba(17,18,20,.2);
  --shadow-pop:  0 4px 8px rgba(17,18,20,.06), 0 40px 70px -30px rgba(17,18,20,.55);
}
*{ box-sizing: border-box; }
[hidden]{ display: none !important; }
html{ -webkit-text-size-adjust: 100%; }
body{
  margin: 0; background: var(--stone); color: var(--ink);
  font-family: var(--font-body); font-size: 16px; line-height: 1.5;
  -webkit-font-smoothing: antialiased;
}
h1,h2,h3{ font-family: var(--font-display); font-weight: 700; letter-spacing: -.02em; line-height: 1.12; margin: 0; font-stretch: 112%; font-variation-settings: "wdth" 112; }
p{ margin: 0; }
a{ color: inherit; }
button, input, select{ font: inherit; color: inherit; }
button{ cursor: pointer; }
:focus-visible{ outline: 3px solid rgba(232,89,12,.5); outline-offset: 2px; }
.skip{ position: absolute; left: -999px; top: 8px; z-index: 100; padding: 10px 16px; background: var(--accent); color: #fff; border-radius: var(--radius); font-weight: 600; }
.skip:focus{ left: 12px; }

/* ---------- Marca ---------- */
.brand-mark{ display: inline-block; flex: none; width: 40px; height: 40px; background: url("data:image/png;base64,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") center / contain no-repeat; transition: transform .5s var(--ease-out); }

/* ---------- Botões ---------- */
.btn{
  display: inline-flex; align-items: center; justify-content: center; gap: 8px; min-height: 42px; padding: 0 20px;
  border-radius: 999px; border: 1px solid transparent; font-weight: 600; text-decoration: none; line-height: 1;
  transition: background .2s var(--ease), border-color .2s var(--ease), color .2s var(--ease), transform .25s var(--ease-out), box-shadow .25s var(--ease-out);
}
.btn-sm{ min-height: 38px; padding: 0 16px; font-size: .9rem; }
.btn-primary{ background: var(--accent); color: #fff; }
.btn-primary:hover{ background: var(--accent-deep); box-shadow: 0 12px 22px -12px rgba(232,89,12,.8); }
.btn-ghost{ background: var(--paper); border-color: rgba(17,18,20,.16); color: var(--ink); }
.btn-ghost:hover{ border-color: var(--ink); background: #fff; }
.btn:not([disabled]):hover{ transform: translateY(-1px); }
.btn:not([disabled]):active{ transform: translateY(1px); }
.btn[disabled]{ opacity: .45; cursor: not-allowed; }
.icon-btn{ display: inline-grid; place-items: center; width: 40px; height: 40px; background: transparent; border: 0; border-radius: 50%; color: inherit; transition: background .2s var(--ease), transform .3s var(--ease-out); }
.icon-btn:hover{ background: rgba(242,239,233,.14); transform: rotate(90deg); }

/* ---------- Estrutura ---------- */
.app{ display: grid; grid-template-columns: 268px minmax(0, 1fr); min-height: 100vh; min-height: 100dvh; }
.side{
  position: sticky; top: 0; height: 100vh; height: 100dvh; display: flex; flex-direction: column; gap: 30px; padding: 24px 16px;
  background: var(--ink); color: var(--stone); border-right: 1px solid var(--line); animation: slide-in .6s var(--ease-out) both;
}
@keyframes slide-in{ from{ opacity: 0; transform: translateX(-14px); } to{ opacity: 1; transform: none; } }
.brand{ display: flex; align-items: center; gap: 12px; padding: 0 10px; text-decoration: none; color: var(--stone); }
.brand:hover .brand-mark{ transform: scale(1.08) rotate(-4deg); }
.brand-text{ font-family: var(--font-display); font-weight: 800; font-size: 1.2rem; line-height: 1.1; letter-spacing: -.01em; font-stretch: 116%; }
.brand-text small{ display: block; margin-top: 3px; font-family: var(--font-body); font-weight: 400; font-size: .8rem; letter-spacing: 0; color: var(--cream-dim); font-stretch: 100%; }
.nav{ display: flex; flex-direction: column; gap: 4px; }
.nav a{
  position: relative; display: flex; align-items: center; gap: 12px; padding: 12px 14px; border-radius: 12px; text-decoration: none;
  color: var(--cream-dim); font-weight: 500;
  transition: background .2s var(--ease), color .2s var(--ease), transform .3s var(--ease-out);
}
.nav a:hover{ background: rgba(242,239,233,.07); color: var(--stone); transform: translateX(3px); }
.nav a[aria-current="page"]{ background: rgba(232,89,12,.16); color: #fff; }
.nav a[aria-current="page"] svg{ color: var(--accent-soft); }
.nav a[aria-current="page"]::before{ content: ""; position: absolute; left: -16px; top: 12px; bottom: 12px; width: 4px; background: var(--accent); border-radius: 0 4px 4px 0; }
.badge{ margin-left: auto; min-width: 22px; padding: 2px 8px; border-radius: 999px; background: var(--accent); color: #fff; font-size: .75rem; font-weight: 700; text-align: center; line-height: 1.5; animation: pulse 2.4s ease-out infinite; }
@keyframes pulse{ 0%,100%{ box-shadow: 0 0 0 0 rgba(232,89,12,.5); } 60%{ box-shadow: 0 0 0 7px rgba(232,89,12,0); } }
.side-foot{ margin-top: auto; display: flex; flex-direction: column; gap: 14px; padding: 18px 10px 0; border-top: 1px solid var(--line); }
.me{ display: flex; align-items: center; gap: 12px; min-width: 0; }
.avatar{ flex: none; display: grid; place-items: center; width: 40px; height: 40px; border-radius: 50%; background: var(--accent); color: #fff; font-family: var(--font-display); font-weight: 700; font-size: 1.1rem; box-shadow: 0 0 0 3px rgba(232,89,12,.28); }
.me-text{ min-width: 0; display: flex; flex-direction: column; }
.me-text strong{ font-size: .95rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.me-text small{ color: var(--cream-dim); font-size: .78rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.side-actions{ display: flex; gap: 8px; }
.side-link{ flex: 1; padding: 9px 10px; text-align: center; text-decoration: none; font-size: .86rem; font-weight: 500; color: var(--stone); background: transparent; border: 1px solid var(--line); border-radius: 999px; transition: border-color .2s var(--ease), background .2s var(--ease); }
.side-link:hover{ border-color: var(--accent); background: rgba(232,89,12,.12); }
.content{ min-width: 0; }
.top{ display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 28px clamp(18px, 3vw, 40px) 10px; }
.top h1{ font-size: clamp(1.7rem, 3vw, 2.3rem); letter-spacing: -.03em; }
.top h1:focus{ outline: none; }
#refreshBtn svg{ transition: transform .6s var(--ease-out); }
#refreshBtn:hover svg{ transform: rotate(180deg); }
#main{ padding: 14px clamp(18px, 3vw, 40px) 64px; max-width: 1320px; }
#main:focus{ outline: none; }
.view:not([hidden]){ animation: rise .55s var(--ease-out) both; }
@keyframes rise{ from{ opacity: 0; transform: translateY(16px); } to{ opacity: 1; transform: none; } }

/* ---------- Faixa de resumo ---------- */
.band{
  position: relative; overflow: hidden; margin-bottom: 26px; padding: 32px 34px 26px; border-radius: 24px;
  background-color: var(--ink); color: var(--stone);
  background-image:
    radial-gradient(circle at 92% 0%, rgba(232,89,12,.4), transparent 45%),
    linear-gradient(rgba(242,239,233,.05) 1px, transparent 1px),
    linear-gradient(90deg, rgba(242,239,233,.05) 1px, transparent 1px);
  background-size: auto, 48px 48px, 48px 48px;
  box-shadow: 0 30px 50px -34px rgba(17,18,20,.7);
}
.band-head h2{ font-size: 1.6rem; letter-spacing: -.03em; }
.band-sub{ margin-top: 4px; color: var(--cream-dim); font-size: .9rem; }
.kpis{ position: relative; z-index: 1; display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); margin: 26px 0 0; border-top: 1px solid var(--line); }
.kpi{ padding: 20px 20px 4px 0; min-width: 0; animation: rise .7s var(--ease-out) both; }
.kpi:nth-child(1){ animation-delay: .08s; } .kpi:nth-child(2){ animation-delay: .16s; } .kpi:nth-child(3){ animation-delay: .24s; } .kpi:nth-child(4){ animation-delay: .32s; }
.kpi + .kpi{ padding-left: 24px; border-left: 1px solid var(--line); }
.kpi dt{ color: var(--cream-dim); font-size: .88rem; }
.kpi dd{ margin: 6px 0 3px; font-family: var(--font-display); font-weight: 700; font-size: clamp(1.8rem, 2.8vw, 2.5rem); letter-spacing: -.03em; line-height: 1.05; overflow-wrap: anywhere; font-stretch: 112%; }
.kpi small{ display: block; min-height: 1.2em; color: var(--cream-dim); font-size: .84rem; }

/* ---------- Painéis ---------- */
.panel{
  margin-bottom: 26px; padding: 24px 26px; background: var(--paper);
  border: 1px solid var(--line-dark); border-radius: var(--radius-lg); box-shadow: var(--shadow-card);
  transition: box-shadow .35s var(--ease-out), border-color .25s var(--ease);
}
.panel:hover{ border-color: rgba(232,89,12,.3); }
.panel-flush{ padding: 0; overflow: hidden; }
.panel-head{ display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-bottom: 16px; }
.panel-head h2{ font-size: 1.15rem; }
.panel-link{ font-size: .9rem; font-weight: 600; color: var(--accent-deep); text-decoration: none; }
.panel-link:hover{ text-decoration: underline; text-underline-offset: 3px; }
.duo{ display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 26px; }
.duo .panel{ margin-bottom: 0; }
#view-visao-geral > .panel{ margin-top: 0; }
.legend{ display: flex; gap: 16px; margin: 0; padding: 0; list-style: none; font-size: .88rem; color: var(--muted); }
.legend li{ display: flex; align-items: center; gap: 7px; }
.sw{ width: 12px; height: 12px; border-radius: 4px; }
.sw-orders, .bar-orders{ background: var(--accent); fill: var(--accent); }
.sw-quotes, .bar-quotes{ background: var(--ink); fill: var(--ink); }
.chart svg{ display: block; width: 100%; height: auto; }
.chart .grid{ stroke: rgba(17,18,20,.08); stroke-width: 1; }
.chart text{ font-family: var(--font-body); font-size: 11px; fill: var(--muted); }
.chart .chart-note{ margin-top: 8px; color: var(--muted); font-size: .9rem; }

/* ---------- Listas curtas ---------- */
.mini{ margin: 0; padding: 0; list-style: none; }
.mini li + li{ border-top: 1px solid var(--line-dark); }
.mini-item{ display: flex; align-items: center; justify-content: space-between; gap: 12px; width: 100%; padding: 13px 10px; background: transparent; border: 0; text-align: left; border-radius: var(--radius); transition: background .2s var(--ease), transform .3s var(--ease-out); }
.mini-item:hover{ background: rgba(232,89,12,.07); transform: translateX(3px); }
.mini-main{ min-width: 0; display: flex; flex-direction: column; }
.mini-main strong{ overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.mini-main small{ color: var(--muted); font-size: .84rem; }
.mini-side{ display: flex; flex-direction: column; align-items: flex-end; gap: 4px; flex: none; }
.mini-side b{ font-family: var(--font-display); font-weight: 700; }

/* ---------- Barra de ferramentas ---------- */
.toolbar{ display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 20px; }
.search{ position: relative; flex: 1 1 280px; min-width: 0; }
.search svg{ position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--muted); pointer-events: none; }
.search input{ width: 100%; height: 46px; padding: 0 18px 0 44px; background: var(--paper); border: 1px solid rgba(17,18,20,.14); border-radius: 999px; transition: border-color .2s var(--ease), box-shadow .25s var(--ease); }
.search input::placeholder{ color: #8a847a; }
.search input:focus, .select:focus{ outline: none; border-color: var(--accent); box-shadow: 0 0 0 4px rgba(232,89,12,.16); }
.select{
  height: 46px; padding: 0 40px 0 18px; background-color: var(--paper); border: 1px solid rgba(17,18,20,.14); border-radius: 999px;
  appearance: none; -webkit-appearance: none; transition: border-color .2s var(--ease), box-shadow .25s var(--ease);
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1.5 6 6.5l5-5' fill='none' stroke='%236B665E' stroke-width='2'/%3E%3C/svg%3E");
  background-repeat: no-repeat; background-position: right 16px center;
}
.toolbar .btn{ min-height: 46px; }

/* ---------- Tabelas ---------- */
.tbl-wrap{ overflow-x: auto; }
.tbl{ width: 100%; border-collapse: collapse; min-width: 760px; }
.tbl th{ padding: 14px 18px; text-align: left; white-space: nowrap; background: var(--ink); color: var(--stone); font-size: .82rem; font-weight: 600; }
.tbl td{ padding: 15px 18px; border-bottom: 1px solid var(--line-dark); vertical-align: middle; font-size: .94rem; }
.tbl tbody tr{ transition: background .2s var(--ease); }
.tbl tbody tr:hover{ background: rgba(232,89,12,.05); }
.tbl tbody tr:last-child td{ border-bottom: 0; }
.tbl .num{ text-align: right; font-variant-numeric: tabular-nums; }
.tbl th.num{ text-align: right; }
.tbl .money{ font-weight: 600; white-space: nowrap; }
.stack{ display: flex; flex-direction: column; min-width: 0; }
.stack small, .sub{ color: var(--muted); font-size: .82rem; overflow-wrap: anywhere; }
.clamp{ display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; max-width: 360px; overflow-wrap: anywhere; }
.link-btn{ padding: 0; background: none; border: 0; text-align: left; font-weight: 700; color: var(--accent-deep); text-decoration: underline; text-decoration-color: transparent; text-underline-offset: 3px; transition: text-decoration-color .2s var(--ease); }
.link-btn:hover{ text-decoration-color: currentColor; }
.wa{ color: inherit; text-decoration: none; white-space: nowrap; border-bottom: 1px dotted var(--muted); }
.wa:hover{ color: var(--accent-deep); border-bottom-color: var(--accent-deep); }
.tag-admin{ display: inline-block; margin-left: 8px; padding: 2px 10px; border-radius: 999px; vertical-align: middle; background: var(--ink); color: var(--stone); font-size: .72rem; font-weight: 600; }
.state{ padding: 56px 24px; text-align: center; color: var(--muted); }
.state strong{ display: block; margin-bottom: 4px; color: var(--ink); font-family: var(--font-display); font-size: 1.1rem; font-weight: 700; }
.state .btn{ margin-top: 16px; }
.state-small{ padding: 18px 6px; text-align: left; }
.pager{ display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; padding: 14px 18px; border-top: 1px solid var(--line-dark); font-size: .9rem; color: var(--muted); }
.pager:empty{ display: none; }
.pager-btns{ display: flex; align-items: center; gap: 12px; }

/* ---------- Status ---------- */
.pill, .status-select{ display: inline-block; padding: 5px 13px; border: 1px solid transparent; border-radius: 999px; font-size: .8rem; font-weight: 600; line-height: 1.3; white-space: nowrap; }
.status-select{
  padding-right: 30px; cursor: pointer; appearance: none; -webkit-appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='7' viewBox='0 0 10 7'%3E%3Cpath d='M1 1.2 5 5.2l4-4' fill='none' stroke='%23111214' stroke-opacity='.65' stroke-width='1.8'/%3E%3C/svg%3E");
  background-repeat: no-repeat; background-position: right 11px center; transition: box-shadow .2s var(--ease);
}
.status-select:hover{ box-shadow: 0 0 0 3px rgba(17,18,20,.07); }
.status-select:disabled{ opacity: .6; cursor: progress; }
.status-select option{ background: #fff; color: var(--ink); }
.tone-new{ background-color: rgba(232,89,12,.13); color: #9A3B00; border-color: rgba(232,89,12,.35); }
.tone-progress{ background-color: rgba(217,164,0,.16); color: #6B5000; border-color: rgba(184,134,0,.4); }
.tone-ok{ background-color: rgba(46,125,79,.13); color: #1F6340; border-color: rgba(46,125,79,.36); }
.tone-off{ background-color: rgba(107,102,94,.12); color: var(--muted); border-color: rgba(107,102,94,.3); }

/* ---------- Gaveta de detalhes ---------- */
.drawer{
  position: fixed; inset: 0 0 0 auto; margin: 0 0 0 auto; padding: 0; border: 0; width: min(560px, 100%); max-width: 100%;
  height: 100vh; height: 100dvh; max-height: none; background: var(--paper); color: var(--ink); box-shadow: var(--shadow-pop);
}
.drawer[open]{ display: flex; flex-direction: column; animation: drawer-in .45s var(--ease-out); }
.drawer::backdrop{ background: rgba(17,18,20,.55); -webkit-backdrop-filter: blur(4px); backdrop-filter: blur(4px); }
@keyframes drawer-in{ from{ transform: translateX(40px); opacity: 0; } to{ transform: none; opacity: 1; } }
.drawer-head{ display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 16px 16px 16px 26px; background: var(--ink); color: var(--stone); flex: none; }
.drawer-head h2{ font-size: 1.35rem; }
.drawer-body{ flex: 1; overflow-y: auto; padding: 26px; }
.d-section{ margin-bottom: 28px; }
.d-section h3{ margin-bottom: 14px; padding-bottom: 10px; border-bottom: 1px solid var(--line-dark); font-size: 1.02rem; }
.kv{ display: grid; grid-template-columns: 120px minmax(0, 1fr); gap: 10px 14px; margin: 0; }
.kv dt{ color: var(--muted); font-size: .9rem; }
.kv dd{ margin: 0; overflow-wrap: anywhere; }
.msg{ margin: 0; padding: 16px 18px; white-space: pre-wrap; overflow-wrap: anywhere; font-family: var(--font-body); font-size: .95rem; background: #fff; border: 1px solid var(--line-dark); border-left: 3px solid var(--accent); border-radius: var(--radius); }
.items{ width: 100%; border-collapse: collapse; font-size: .9rem; }
.items th{ padding: 9px 12px; text-align: left; background: var(--sand); font-size: .8rem; font-weight: 600; }
.items td{ padding: 11px 12px; border-bottom: 1px solid var(--line-dark); vertical-align: top; }
.items .num{ text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
.items-wrap{ overflow-x: auto; background: #fff; border: 1px solid var(--line-dark); border-radius: var(--radius); }
.total-row{ display: flex; align-items: baseline; justify-content: space-between; gap: 12px; margin-top: 16px; padding-top: 16px; border-top: 2px solid var(--ink); }
.total-row strong{ font-family: var(--font-display); font-size: 1.8rem; letter-spacing: -.03em; color: var(--accent-deep); }

/* ---------- Aviso ---------- */
.toast{
  position: fixed; left: 50%; bottom: 26px; z-index: 200; max-width: min(92vw, 460px); padding: 14px 20px;
  background: var(--ink); color: var(--stone); border-left: 4px solid var(--accent); border-radius: 14px; box-shadow: var(--shadow-pop);
  font-size: .94rem; opacity: 0; pointer-events: none; transform: translate(-50%, 14px);
  transition: opacity .3s var(--ease), transform .45s var(--ease-out);
}
.toast.show{ opacity: 1; transform: translate(-50%, 0); }
.toast.error{ border-left-color: #E04B4B; }
.noscript{ padding: 24px; text-align: center; }

/* ---------- Acesso negado ---------- */
.denied{ display: grid; place-items: center; min-height: 100vh; min-height: 100dvh; padding: 24px; background: var(--ink); color: var(--stone); }
.denied-box{ max-width: 440px; animation: rise .7s var(--ease-out) both; }
.denied-box h1{ margin: 24px 0 10px; font-size: 2.1rem; }
.denied-box p{ margin-bottom: 26px; color: var(--cream-dim); }

/* ---------- Responsivo ---------- */
@media (max-width: 1000px){
  .app{ grid-template-columns: 1fr; }
  .side{ position: static; height: auto; flex-direction: row; flex-wrap: wrap; align-items: center; gap: 10px 16px; padding: 14px 16px; border-right: 0; border-bottom: 1px solid var(--line); animation: none; }
  .nav{ order: 3; flex-direction: row; width: 100%; overflow-x: auto; padding-bottom: 2px; }
  .nav a{ flex: none; padding: 10px 14px; }
  .nav a:hover{ transform: none; }
  .nav a[aria-current="page"]::before{ left: 14px; right: 14px; top: auto; bottom: 0; width: auto; height: 3px; border-radius: 3px 3px 0 0; }
  .side-foot{ margin: 0 0 0 auto; padding: 0; border: 0; flex-direction: row; align-items: center; }
  .me-text{ display: none; }
  .kpis{ grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .kpi:nth-child(3){ padding-left: 0; border-left: 0; }
  .kpi:nth-child(n+3){ border-top: 1px solid var(--line); margin-top: 14px; }
  .duo{ grid-template-columns: 1fr; }
}
@media (max-width: 760px){
  .tbl{ min-width: 0; }
  .tbl thead{ display: none; }
  .tbl, .tbl tbody{ display: block; }
  .tbl tr{ display: block; padding: 14px 18px; border-bottom: 1px solid var(--line-dark); }
  .tbl tbody tr:last-child{ border-bottom: 0; }
  .tbl td{ display: flex; justify-content: space-between; align-items: center; gap: 16px; padding: 6px 0; border: 0; text-align: right; }
  .tbl td::before{ content: attr(data-label); flex: none; color: var(--muted); font-size: .82rem; text-align: left; }
  .tbl td .stack{ align-items: flex-end; }
  .tbl .num, .tbl th.num{ text-align: right; }
  .clamp{ max-width: 100%; }
  .toolbar .btn{ flex: 1 1 100%; }
  .select{ flex: 1 1 100%; }
}
@media (max-width: 480px){
  .band{ padding: 24px 20px 20px; border-radius: 20px; }
  .kpis{ grid-template-columns: 1fr; }
  .kpi, .kpi + .kpi{ padding: 14px 0 4px; border-left: 0; }
  .kpi + .kpi{ border-top: 1px solid var(--line); margin-top: 8px; }
  .kpi:nth-child(n+3){ margin-top: 8px; }
  .kv{ grid-template-columns: 1fr; gap: 2px 0; }
  .kv dd{ margin-bottom: 10px; }
}
.bar-revenue{ fill: var(--accent); }
@media (prefers-reduced-motion: reduce){
  *, *::before, *::after{ animation: none !important; transition-duration: .001ms !important; }
}

</style>
</head>
<body>
<a class="skip" href="#main">Pular para o conteúdo</a>

<div class="app">

  <aside class="side">
    <a class="brand" href="#/visao-geral">
      <span class="brand-mark" aria-hidden="true"></span>
      <span class="brand-text">ConstrutoMAX<small>Painel admin</small></span>
    </a>

    <nav class="nav" aria-label="Seções do painel">
      <a href="#/visao-geral" data-route="visao-geral">
        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/></svg>
        <span>Visão geral</span>
      </a>
      <a href="#/pedidos" data-route="pedidos">
        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 8 12 3 3 8v8l9 5 9-5z"/><path d="M3 8l9 5 9-5M12 13v8"/></svg>
        <span>Pedidos</span>
        <span class="badge" id="navPedidos" hidden></span>
      </a>
      <a href="#/orcamentos" data-route="orcamentos">
        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M14 3v6h6M8 13h8M8 17h5"/></svg>
        <span>Orçamentos</span>
        <span class="badge" id="navOrcamentos" hidden></span>
      </a>
      <a href="#/clientes" data-route="clientes">
        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="9" cy="8" r="4"/><path d="M2 21a7 7 0 0 1 14 0"/><path d="M16 4a4 4 0 0 1 0 8M22 21a7 7 0 0 0-4-6.3"/></svg>
        <span>Clientes</span>
      </a>
      <a href="#/faturamento" data-route="faturamento">
        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 17l6-6 4 4 8-8"/><path d="M14 7h7v7"/></svg>
        <span>Faturamento</span>
      </a>
    </nav>

    <div class="side-foot">
      <div class="me">
        <span class="avatar" aria-hidden="true"><?= h($inicial) ?></span>
        <div class="me-text">
          <strong><?= h($nome) ?></strong>
          <small><?= h($admin['email']) ?></small>
        </div>
      </div>
      <div class="side-actions">
        <a class="side-link" href="index.html">Voltar ao site</a>
        <button type="button" class="side-link" id="logoutBtn">Sair</button>
      </div>
    </div>
  </aside>

  <div class="content">
    <header class="top">
      <h1 id="pageTitle" tabindex="-1">Visão geral</h1>
      <button type="button" class="btn btn-ghost btn-sm" id="refreshBtn">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 12a9 9 0 1 1-3-6.7L21 8"/><path d="M21 3v5h-5"/></svg>
        Atualizar
      </button>
    </header>

    <main id="main" tabindex="-1">

      <!-- VISÃO GERAL -->
      <section class="view" id="view-visao-geral" hidden>
        <div class="band">
          <div class="band-head">
            <h2>Resumo da loja</h2>
            <p class="band-sub" id="updatedAt">Carregando…</p>
          </div>
          <dl class="kpis">
            <div class="kpi">
              <dt>Vendas confirmadas</dt>
              <dd id="kpiSales">—</dd>
              <small id="kpiSalesSub"></small>
            </div>
            <div class="kpi">
              <dt>Pedidos</dt>
              <dd id="kpiOrders">—</dd>
              <small id="kpiOrdersSub"></small>
            </div>
            <div class="kpi">
              <dt>Orçamentos</dt>
              <dd id="kpiQuotes">—</dd>
              <small id="kpiQuotesSub"></small>
            </div>
            <div class="kpi">
              <dt>Clientes</dt>
              <dd id="kpiUsers">—</dd>
              <small id="kpiUsersSub"></small>
            </div>
          </dl>
        </div>

        <div id="overviewError" class="panel" hidden></div>

        <section class="panel" aria-labelledby="chartTitle">
          <div class="panel-head">
            <h2 id="chartTitle">Movimento dos últimos 14 dias</h2>
            <ul class="legend">
              <li><span class="sw sw-orders"></span>Pedidos</li>
              <li><span class="sw sw-quotes"></span>Orçamentos</li>
            </ul>
          </div>
          <div id="chartHost" class="chart"></div>
        </section>

        <div class="duo">
          <section class="panel" aria-labelledby="recentOrdersTitle">
            <div class="panel-head">
              <h2 id="recentOrdersTitle">Últimos pedidos</h2>
              <a class="panel-link" href="#/pedidos">Ver todos</a>
            </div>
            <ul class="mini" id="recentOrders"></ul>
          </section>
          <section class="panel" aria-labelledby="recentQuotesTitle">
            <div class="panel-head">
              <h2 id="recentQuotesTitle">Últimos orçamentos</h2>
              <a class="panel-link" href="#/orcamentos">Ver todos</a>
            </div>
            <ul class="mini" id="recentQuotes"></ul>
          </section>
        </div>
      </section>

      <!-- PEDIDOS -->
      <section class="view" id="view-pedidos" hidden>
        <div class="toolbar">
          <div class="search">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            <input type="search" id="pedidosSearch" placeholder="Buscar por cliente, WhatsApp, e-mail ou nº do pedido" aria-label="Buscar pedidos" autocomplete="off" maxlength="100">
          </div>
          <select id="pedidosStatus" class="select" aria-label="Filtrar pedidos por status"></select>
          <button type="button" class="btn btn-ghost" id="pedidosExport">Exportar CSV</button>
        </div>
        <section class="panel panel-flush">
          <div id="pedidosList"></div>
          <div class="pager" id="pedidosPager"></div>
        </section>
      </section>

      <!-- ORÇAMENTOS -->
      <section class="view" id="view-orcamentos" hidden>
        <div class="toolbar">
          <div class="search">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            <input type="search" id="orcamentosSearch" placeholder="Buscar por cliente, WhatsApp, e-mail, texto ou nº" aria-label="Buscar orçamentos" autocomplete="off" maxlength="100">
          </div>
          <select id="orcamentosStatus" class="select" aria-label="Filtrar orçamentos por status"></select>
          <button type="button" class="btn btn-ghost" id="orcamentosExport">Exportar CSV</button>
        </div>
        <section class="panel panel-flush">
          <div id="orcamentosList"></div>
          <div class="pager" id="orcamentosPager"></div>
        </section>
      </section>

      <!-- CLIENTES -->
      <section class="view" id="view-clientes" hidden>
        <div class="toolbar">
          <div class="search">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            <input type="search" id="clientesSearch" placeholder="Buscar por nome, e-mail ou WhatsApp" aria-label="Buscar clientes" autocomplete="off" maxlength="100">
          </div>
          <button type="button" class="btn btn-ghost" id="clientesExport">Exportar CSV</button>
        </div>
        <section class="panel panel-flush">
          <div id="clientesList"></div>
          <div class="pager" id="clientesPager"></div>
        </section>
      </section>

      <!-- FATURAMENTO -->
      <section class="view" id="view-faturamento" hidden>
        <div class="toolbar">
          <select id="faturamentoPeriod" class="select" aria-label="Período do faturamento">
            <option value="7d">Últimos 7 dias</option>
            <option value="30d" selected>Últimos 30 dias</option>
            <option value="90d">Últimos 90 dias</option>
            <option value="month">Este mês</option>
            <option value="year">Este ano</option>
          </select>
        </div>

        <div class="band">
          <div class="band-head">
            <h2>Faturamento</h2>
            <p class="band-sub">Conta pedidos pagos, em separação, enviados e entregues.</p>
          </div>
          <dl class="kpis">
            <div class="kpi">
              <dt>Faturamento</dt>
              <dd id="fatKpiRevenue">—</dd>
              <small id="fatKpiRevenueSub"></small>
            </div>
            <div class="kpi">
              <dt>Pedidos confirmados</dt>
              <dd id="fatKpiOrders">—</dd>
              <small id="fatKpiOrdersSub"></small>
            </div>
            <div class="kpi">
              <dt>Ticket médio</dt>
              <dd id="fatKpiAvg">—</dd>
              <small id="fatKpiAvgSub"></small>
            </div>
            <div class="kpi">
              <dt>A receber</dt>
              <dd id="fatKpiPending">—</dd>
              <small id="fatKpiPendingSub"></small>
            </div>
          </dl>
        </div>

        <div id="faturamentoError" class="panel" hidden></div>

        <section class="panel" aria-labelledby="fatChartTitle">
          <div class="panel-head">
            <h2 id="fatChartTitle">Faturamento por dia</h2>
          </div>
          <div id="fatChartHost" class="chart"></div>
        </section>

        <section class="panel" aria-labelledby="fatPayTitle">
          <div class="panel-head">
            <h2 id="fatPayTitle">Por forma de pagamento</h2>
          </div>
          <div id="fatPayList"></div>
        </section>
      </section>

    </main>
  </div>
</div>

<dialog class="drawer" id="drawer" aria-labelledby="drawerTitle">
  <div class="drawer-head">
    <h2 id="drawerTitle"></h2>
    <button type="button" class="icon-btn" id="drawerClose" aria-label="Fechar detalhes">
      <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
    </button>
  </div>
  <div class="drawer-body" id="drawerBody"></div>
</dialog>

<div class="toast" id="toast" role="status" aria-live="polite"></div>

<noscript><p class="noscript">O painel precisa de JavaScript ativado para funcionar.</p></noscript>
<script src="admin.js"></script>
</body>
</html>