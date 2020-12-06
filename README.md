# Posting-Erinnerung 1.0
Das Plugin erinnert alle User an inaktive Szenen. Szenen gelten als inaktiv, wenn dort seit länger als x Tagen nicht gepostet wurde. Die Frist von x Tagen lässt sich im AdminCP einstellen. User können entweder über einen Banner informiert werden oder über eine Box in welcher die Szenen dargestellt sind.
Zudem können sich Admins eine Liste an allen inaktiven Szene ausgeben lassen.

## Funktionen
* Anzeige von inaktiven Szenen (Frist setzbar im AdminCP)
* User werden informiert, wenn sie in einer Szene dran sind
* Dies geschieht entweder über einen Banner oder einer Infobox
* Admins können sich eine Liste von allen inaktiven Szenen ausgeben und wer dran ist

## Voraussetzungen
* [Enhanced Account Switcher](http://doylecc.altervista.org/bb/downloads.php?dlid=26&cat=2) muss installiert sein 
* [Inplaytracker 2.0](https://github.com/its-sparks-fly/Inplaytracker-2.0) muss installiert sein 

## Template-Änderungen
__Neue globale Templates:__
* postingreminder
* postingreminderCharacters
* postingreminderScenes
* postingreminderHeader

## Variablen einbauen
Man hat zwei Möglichkeiten User zu informieren:

1. `{$characterOpenScenes}` kann an eine beliebige Stelle im Forum gesetzt werden (am besten Header oder Footer) und gibt eine kurze Übersicht (siehe Bilder)

2. `{$header_postingreminder}` kann an eine beliebige Stelle im Forum gesetzt werden, aber sollte am besten im header-Template bei den anderen Bannerns landen. Der Banner informiert über inaktive Szenen und enthält einen Link auf die Seite auf welcher man alles genau nachlesen kann

## Vorschaubilder
__Einstellungen des Posting-Erinnerung-Plugin__
![Posting-Erinnerung Einstellungen](https://aheartforspinach.de/upload/plugins/postingreminder_settings.png)

__Admin-Tool für inaktive Szenen__
![Posting-Erinnerung Admin-Tool](https://aheartforspinach.de/upload/plugins/postingreminder_admin.png)

__Übersichtsseite__
![Posting-Erinnerung Übersichtsseite](https://aheartforspinach.de/upload/plugins/postingreminder_page.png)

__Übersicht für den Header/Footer__
![Posting-Erinnerung Übersicht](https://aheartforspinach.de/upload/plugins/postingreminder_overview.png)
