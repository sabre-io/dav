<?php

class Sabre_DAV_HTMLDirectoriesFilter extends Sabre_DAV_FilterTree {

    function get($path) {

        try {

            return parent::get($path);

        } catch (Sabre_DAV_NotImplementedException $e) {

            return $this->generateHTML($path);            

        }

    }

    function getNodeInfo($path,$depth=0) {

        $nodeInfo = parent::getNodeInfo($path,$depth);

        foreach($nodeInfo as &$node) {

            if ($node['type'] == Sabre_DAV_Server::NODE_DIRECTORY) {

                if (isset($node['size'])) unset($node['size']);
                $node['contenttype'] = 'text/html; encoding=utf-8';

            }

        }

        return $nodeInfo;

    }

    function generateHTML($path) {

        ob_start();
        
        echo '<html>';
        echo '<head>';
        
        echo '<title>SabreDAV - /' . htmlspecialchars($path,ENT_QUOTES,'UTF-8') . '</title>';
        echo '<style type="text/css">body { font-family: sans-serif; }</style>';
        echo '</head>';

        echo '<body>';
        echo '<h1>SabreDAV  - /' . htmlspecialchars($path,ENT_QUOTES,'UTF-8') . '</h1>';

        $nodes = $this->getNodeInfo($path,1);

        echo '<table>';
        
        echo '<tr><th></th><th>Name</th><th>Size</th><th>Type</th><th>Modified</th></tr>';
        foreach($nodes as $node) {
            if ($node['name']!='')
                $this->generateNodeHTML($node);
        }

        echo '</table>';

        echo '</body>';

        echo '</html>';

        return ob_get_clean();

    }

    function generateNodeHTML($node) {

        echo '<tr>';
        echo '<td></td>';
        
        echo '<td><a href="' . htmlspecialchars($node['name'],ENT_QUOTES,'UTF-8') . '">' . htmlspecialchars($node['name'],ENT_QUOTES,'UTF-8') . '</td>';
        if (isset($node['size'])) {
            echo '<td>' . htmlspecialchars($node['size'],ENT_QUOTES,'UTF-8') . '</td>';
        } else {
            echo '<td></td>';
        }

        if ($node['type']==Sabre_DAV_Server::NODE_DIRECTORY) {
            
            echo '<td>Directory</td>';
        } else {
            if (isset($node['contenttype'])) {
                echo '<td>' . htmlspecialchars($node['contenttype'],ENT_QUOTES,'UTF-8') . '</td>';
            } else {
                echo '<td></td>';
            }
        }
        echo '<td>' . date(DateTime::COOKIE,$node['lastmodified']) . '</td>';

        echo '</tr>';


    }


}
