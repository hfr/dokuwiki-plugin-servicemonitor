<?php

if (!defined('DOKU_INC')) {
    exit;
}

class syntax_plugin_servicemonitor extends DokuWiki_Syntax_Plugin
{
    public function getType()
    {
        return 'substition';
    }

    public function getPType()
    {
        return 'block';
    }

    public function getSort()
    {
        return 160;
    }

    public function connectTo($mode)
    {
        global $ID;

	if ($this->isPageAllowed($ID)) {
            $this->Lexer->addSpecialPattern(
                '~~service-monitor(?:\(intervall=[\d.]+\))?~~',
                $mode,
                'plugin_servicemonitor'
            );
        }
    }

    private function isPageAllowed($pageId)
    {
        $allowedPages = $this->getConf('allowed_pages');

        if (!is_array($allowedPages) || empty($allowedPages)) {
            return true;
        }

        $pageId = cleanID($pageId);

        foreach ($allowedPages as $allowedPage) {
            if (cleanID(trim($allowedPage)) === $pageId) {
                return true;
            }
        }

        return false;
    }

    public function handle($match, $state, $pos, Doku_Handler $handler)
    {
        $intervall = (float) ($conf['plugin']['servicemonitor']['intervall'] ?? '10.0');

        if (preg_match('/intervall=([\d.]+)/', $match, $matches)) {
            $intervall = max(0.5, (float)$matches[1]);
        }
        return [(int)($intervall*1000)];
    }

    public function render($mode, Doku_Renderer $renderer, $data)
    {
        if ($mode !== 'xhtml') {
            return false;
        }

        $renderer->info['cache'] = false;

        global $conf, $ID;

        $config = $conf['plugin']['servicemonitor']['services'] ?? '';
        $services = [];

        foreach (preg_split('/\r?\n/', $config) as $line) {
            $line = trim($line);

            if ($line === '' || strpos($line, '|') === false) {
                continue;
            }

            [$name, $description] = explode('|', $line, 2);

            $name = trim($name);
            $description = trim($description);

            if ($name === '') {
                continue;
            }

            $services[] = [
                'name' => $name,
                'description' => $description,
                'status' => 'Lade ...',
            ];
        }
        $services = $this->applyAccessStatus($services, $ID);
	$renderer->doc .= '<div class="service-monitor" data-page="' . hsc($ID) . '" data-no-login="' . hsc($this->getLang('no_login')) . '" data-no-permission="' . hsc($this->getLang('no_permission')) . '" data-access-denied="' . hsc($this->getLang('access_denied')) . '">';
        $renderer->doc .= '<table class="inline servicemonitor">';
        $renderer->doc .= '<thead><tr>';
        $renderer->doc .= '<th>Dienst</th>';
        $renderer->doc .= '<th>Beschreibung</th>';
        $renderer->doc .= '<th class="centeralign">Status</th>';
        $renderer->doc .= '</tr></thead><tbody>';

        foreach ($services as $service) {
            $name = hsc($service['name']);
            $description = hsc($service['description']);
            $status = hsc($service['status']);
            $renderer->doc .= '<tr>';
            $renderer->doc .= '<td>' . $name . '</td>';
            $renderer->doc .= '<td>' . $description . '</td>';
            $renderer->doc .= '<td class="centeralign">';
            $renderer->doc .= '<span class="servicemonitor-status" ';
            $renderer->doc .= 'data-service="' . $name . '">';
            $renderer->doc .= $status;
            $renderer->doc .= '</span>';
            $renderer->doc .= '</td>';
            $renderer->doc .= '</tr>';
        }

        $renderer->doc .= '</tbody></table>';
	$renderer->doc .= '</div>';
	if ($this->hasAccess($ID)) {
            $renderer->doc .= '<script>document.addEventListener("DOMContentLoaded", () => {window.updateServiceStatus(' . json_encode($ID) . ');setInterval(() => {window.updateServiceStatus(' . json_encode($ID) . ');}, ' . $data[0] . ');});</script>';
        }
        return true;
    }

public function pageAllowed($pageId)
{
    $pageId = cleanID($pageId);
    // Check allowed pages
    $allowedPages = $this->getConf('allowed_pages');

    if (empty($allowedPages)) {
        return true;
    }
    $allowedPages = array_map(static fn($id) => cleanID(trim($id)), $allowedPages);
    if (!in_array($pageId, $allowedPages, true)) {
        return false;
    }
    return true;
}

public function hasAccess($pageId)
{
    $pageId = cleanID($pageId);

    // Check page ID
    if (empty($pageId)) {
        return false;
    }
    if ($this->getConf('restrict_to_login') &&
        empty($_SERVER['REMOTE_USER'])) {
       return false;
    }
    // Check allowed pages
    $allowedPages = $this->getConf('allowed_pages');

    if (!$this->pageAllowed($pageId)) {
        return false;
    }
    if (auth_quickaclcheck($pageId) < AUTH_READ) {
        return false;
    }
    return true;
}

public function getError($pageId)
{
    $error = null;
    $pageId = cleanID($pageId);
    if (empty($pageId)) {
        $error = 'invalid_page';
    } elseif ($this->getConf('restrict_to_login') &&
        empty($_SERVER['REMOTE_USER'])) {
        $error = 'no_login';
    } elseif (!$this->pageAllowed($pageId)) {
        $error = 'page_not_allowed';
    } elseif (auth_quickaclcheck($pageId) < AUTH_READ) {
        $error = 'no_permission';
    }
    return $error;
}

/**
 * Applies access restrictions to the service status array.
 *
 * Every service entry must contain a 'status' field.
 * If access is denied, the status is replaced with
 * the corresponding localized error message.
 *
 * @param array  $services Existing service status array
 * @param string $pageId   Wiki page ID
 * @return array Modified service status array
 */
public function applyAccessStatus(array $services, $pageId)
{
    $pageId = cleanID($pageId);

    $has_access = $this->hasAccess($pageId);
    if ($has_access) {
        return $services;
    }

    $message = $this->getLang($this->getError($pageId));

    foreach ($services as &$service) {
        $service['status'] = $message;
    }

    return $services;
}

}

