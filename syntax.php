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
        $this->Lexer->addSpecialPattern(
            '~~service-monitor(?:\(intervall=[\d.]+\))?~~',
            $mode,
            'plugin_servicemonitor'
        );
    }

    public function handle($match, $state, $pos, Doku_Handler $handler)
    {
        $intervall = $conf['plugin']['servicemonitor']['intervall'] ?? 10.0;

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

        global $conf;

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
            ];
        }

        $renderer->doc .= '<table class="inline servicemonitor">';
        $renderer->doc .= '<thead><tr>';
        $renderer->doc .= '<th>Dienst</th>';
        $renderer->doc .= '<th>Beschreibung</th>';
        $renderer->doc .= '<th class="centeralign">Status</th>';
        $renderer->doc .= '</tr></thead><tbody>';

        foreach ($services as $service) {
            $name = hsc($service['name']);
            $description = hsc($service['description']);

            $renderer->doc .= '<tr>';
            $renderer->doc .= '<td>' . $name . '</td>';
            $renderer->doc .= '<td>' . $description . '</td>';
            $renderer->doc .= '<td class="centeralign">';
            $renderer->doc .= '<span class="servicemonitor-status" ';
            $renderer->doc .= 'data-service="' . $name . '">';
            $renderer->doc .= 'Lade …';
            $renderer->doc .= '</span>';
            $renderer->doc .= '</td>';
            $renderer->doc .= '</tr>';
        }

        $renderer->doc .= '</tbody></table>';

	$renderer->doc .= '<script>document.addEventListener("DOMContentLoaded",()=>{window.updateServiceStatus();setInterval(window.updateServiceStatus,'.$data[0].');});</script>';

        return true;
    }
}
