# Extension "all"

Diese Extension hält einige *global verfügbare* Funktionen und Einstellungen vor, die nicht in anderen Extensions vorkommen bzw. keiner eigenen Extension bedürfen.

Zu der zentralen Extension unter "backend/extensions/all/" existiert ggf. noch eine "Schwester-Extension" unter "projects/[Projektverzeichnis]/extensions/all/".

## Konfiguration

Hier sind einige Konfigurationen für das Backend hinterlegt. Die Werte sind in Arrays gekapselt damit die zentralen Werte durch Werte in der jeweiligen Projekt-Konfiguration unter project/extensions/all/config/config.php überschriben werden können.

* theme: Das Default-Theme im Backend
* autolog: automatischer Login ohne Username/Passwort (sinnvoll bei lokalem Einsatz)
* wizards: ...

Beispiel

	"theme":  ["humanity"],
	"wizards":  [],
	"autolog":  [0]

## Hooks

In der Datei "hooks.php" sind die zentralen Backend-Hooks hinterlegt. Sie "hören" auf bestimmte Aktionen im Backend und werden **vor** oder **nach** der jeweiligen Aktion ausgeführt.

### Nutzung

In der Datei "hooks.php" kann überprüft werden, welche Hook-Funktionen bereits global vorhanden sind. 

Zentrale Hooks stehen allen Projekten zur Verfügung und sollten nur von den Maintainern des Gesamt-Systems angelegt werden (**daher keine Schreibrechte für "hooks.php"**)!!

Projekt-bezogene Hooks lassen sich als Projekt-Extension separat anlegen und verwalten.
