<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/urls.php';

$U = [
    'activeLangs' => ['en', 'tr'],
    'ids' => ['software-developer' => ['en' => 'software-developer', 'tr' => 'yazilim-gelistirici'],
              'accountant'         => ['en' => 'accountant']],
    'published' => ['software-developer' => ['en', 'tr'], 'accountant' => ['en']],
    'pageSlugs' => ['en' => ['methodology' => 'methodology'],
                    'tr' => ['methodology' => 'metodoloji']],
];

t_eq('https://willaistealit.com/',    url_for('en', 'home', '', $U), 'EN ana sayfa prefix siz');
t_eq('https://willaistealit.com/tr/', url_for('tr', 'home', '', $U), 'TR ana sayfa');
t_eq('https://willaistealit.com/software-developer',
     url_for('en', 'job', 'software-developer', $U), 'EN entry');
t_eq('https://willaistealit.com/tr/yazilim-gelistirici',
     url_for('tr', 'job', 'software-developer', $U), 'TR entry');
t_eq('https://willaistealit.com/tr/metodoloji',
     url_for('tr', 'page', 'methodology', $U), 'TR sabit sayfa');
t_eq('https://willaistealit.com/og/accountant.png',
     url_for('en', 'og', 'accountant', $U), 'EN OG dil klasorsuz');
t_eq('https://willaistealit.com/og/tr/yazilim-gelistirici.png',
     url_for('tr', 'og', 'software-developer', $U), 'TR OG');

// Alternates YALNIZCA yayinlanan dillerden kurulur (spec 5.1).
t_eq(['en' => 'https://willaistealit.com/software-developer',
      'tr' => 'https://willaistealit.com/tr/yazilim-gelistirici',
      'x-default' => 'https://willaistealit.com/software-developer'],
     alternates_for('job', 'software-developer', $U), 'iki dilde yayinli');

t_eq(['en' => 'https://willaistealit.com/accountant',
      'x-default' => 'https://willaistealit.com/accountant'],
     alternates_for('job', 'accountant', $U), 'TR satiri HIC basilmaz');

t_eq(['en' => 'https://willaistealit.com/methodology',
      'tr' => 'https://willaistealit.com/tr/metodoloji',
      'x-default' => 'https://willaistealit.com/methodology'],
     alternates_for('page', 'methodology', $U), 'sabit sayfa alternates');
