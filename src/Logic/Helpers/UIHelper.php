<?php

class UIHelper {

    public static function getIconForEspecialidad($nombre) {
        $nombre = strtolower($nombre);
        $svg = '';
        $iconClass = 'icon-general';

        if (strpos($nombre, 'odon') !== false || strpos($nombre, 'dental') !== false || strpos($nombre, 'dient') !== false) {
            $svg = '<svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M7 2c-2.35 0-4.41 1.55-5.06 3.8-.3 1.05-.44 2.15-.44 3.2v.5c0 4.14 3.36 7.5 7.5 7.5.83 0 1.5-.67 1.5-1.5s-.67-1.5-1.5-1.5c-2.48 0-4.5-2.02-4.5-4.5v-.5c0-.75.1-1.48.29-2.18.36-1.25 1.51-2.12 2.81-2.12.83 0 1.5-.67 1.5-1.5s-.67-1.2-1.5-1.2zm10 0c-2.35 0-4.41 1.55-5.06 3.8-.3 1.05-.44 2.15-.44 3.2v.5c0 4.14 3.36 7.5 7.5 7.5.83 0 1.5-.67 1.5-1.5s-.67-1.5-1.5-1.5c-2.48 0-4.5-2.02-4.5-4.5v-.5c0-.75.1-1.48.29-2.18.36-1.25 1.51-2.12 2.81-2.12.83 0 1.5-.67 1.5-1.5s-.67-1.2-1.5-1.2z"/></svg>';
            $iconClass = 'icon-dental';
        } elseif (strpos($nombre, 'general') !== false || strpos($nombre, 'medicina') !== false || strpos($nombre, 'familiar') !== false) {
            $svg = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>';
        } elseif (strpos($nombre, 'pediatr') !== false || strpos($nombre, 'niño') !== false || strpos($nombre, 'infant') !== false) {
            $svg = '<svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2c1.1 0 2 .9 2 2s-.9 2-2 2-2-.9-2-2 .9-2 2-2zm9 7h-6v13h-2v-6h-2v6H9V9H3V7h18v2z"/></svg>';
            $iconClass = 'icon-pediatry';
        } elseif (strpos($nombre, 'ginec') !== false || strpos($nombre, 'obst') !== false || strpos($nombre, 'mujer') !== false) {
            $svg = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="9" r="4"/><path d="M12 13v9M9 19h6"/></svg>';
            $iconClass = 'icon-gineco';
        } elseif (strpos($nombre, 'cardio') !== false || strpos($nombre, 'corazon') !== false || strpos($nombre, 'vascular') !== false) {
            $svg = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l8.78-8.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>';
            $iconClass = 'icon-cardio';
        } elseif (strpos($nombre, 'trauma') !== false || strpos($nombre, 'ortop') !== false || strpos($nombre, 'hues') !== false) {
            $svg = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>';
        } elseif (strpos($nombre, 'oftal') !== false || strpos($nombre, 'ojo') !== false || strpos($nombre, 'visual') !== false || strpos($nombre, 'vista') !== false) {
            $svg = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
        } elseif (strpos($nombre, 'dermat') !== false || strpos($nombre, 'piel') !== false) {
            $svg = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2a4 4 0 0 0-4 4c0 2 4 6 4 6s4-4 4-6a4 4 0 0 0-4-4z"/><path d="M12 14v8"/><path d="M8 18h8"/></svg>';
        } elseif (strpos($nombre, 'neurol') !== false || strpos($nombre, 'cerebr') !== false) {
            $svg = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2a7 7 0 0 0-7 7c0 2.5 1.5 4.5 3 6l1 7h6l1-7c1.5-1.5 3-3.5 3-6a7 7 0 0 0-7-7z"/><circle cx="12" cy="9" r="2"/></svg>';
        } elseif (strpos($nombre, 'psicol') !== false || strpos($nombre, 'psiqu') !== false || strpos($nombre, 'mental') !== false) {
            $svg = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>';
            $iconClass = 'icon-gineco';
        } else {
            $svg = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>';
        }

        return ['svg' => $svg, 'class' => $iconClass];
    }
}
