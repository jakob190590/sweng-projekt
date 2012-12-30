<?php
require_once __DIR__.'/../lib/DBAccess.php';
require_once __DIR__.'/LockProgrammerConfig.php';
require_once __DIR__.'/AccessListItem.php';

class LockProgrammerSynchronizer
{
    function synchronize($lockProgrammer)
    {
        $dbh = new DBAccess();
        
        // Von Programmiergeraet in DB
        $queryStmt = $dbh->prepare("SELECT last_change FROM `lock` WHERE LockId = ?");
        $updatStmt = $dbh->prepare("UPDATE `lock` SET last_sync = NOW() WHERE LockId = ?");
        foreach ($lockProgrammer->getConfigList() as $cfg) {
            if ($cfg->inSync) {
                $queryStmt->bindParam(1, $cfg->lockId, PDO::PARAM_INT);
                $queryStmt->execute();
                $lastChange = $queryStmt->fetchColumn();
                if ($lastChange == $cfg->lastChange) {
                    // Keine Aenderung, waehrend LockProgrammer unterwegs war
                    // last_sync in Tabelle `lock` aktualisieren!
                    $updatStmt->bindParam(1, $cfg->lockId, PDO::PARAM_INT);
                    $updatStmt->execute();
                }
            }
        }
        
        // Von DB auf Programmiergeraet
        $list = array();
        $locks = $dbh->query("SELECT LockId, Location, last_change FROM `lock` WHERE last_change > last_sync OR last_sync IS NULL");
        while ($lock = $locks->fetchObject()) {            
            $wlres = $dbh->pquery("SELECT KeyId FROM whitelist WHERE LockId = ?", $lock->LockId);
            $blres = $dbh->pquery("SELECT KeyId FROM blacklist WHERE LockId = ?", $lock->LockId);
            $alres = $dbh->pquery("SELECT KeyId, Begin, End FROM access NATURAL JOIN `key` WHERE Aktiv = FALSE AND LockId = ?", $lock->LockId);
            // LockConfig anlegen
            $wl = array();
            $bl = array();
            $al = array();
            while ($id = $wlres->fetchColumn()) $wl[] = $id;
            while ($id = $blres->fetchColumn()) $bl[] = $id;
            while ($row = $alres->fetchObject()) {
                $al[] = new AccessListItem($row->KeyId, $row->Begin, $row->End);
            }
            $lockConfig = new LockConfig($al, $wl, $bl);
            // LockProgrammerConfig anlegen und zur Liste hinzufuegen
            $lockProgrammerConfig = new LockProgrammerConfig($lock->LockId, $lock->Location, $lockConfig, $lock->last_change);
            $list[] = $lockProgrammerConfig;
        }
        
        // Auf Programmiergeraet uebertragen
        $lockProgrammer->setConfigList($list);
    }
}
