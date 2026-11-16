<?php
declare(strict_types=1);

/**
 * UI helpers for Gym–Trainer links and Trainer specialties display.
 * Compatible with current single gym_id / specialty_id and future arrays from backend.
 */

if (!function_exists('gf_ui_trainer_gym_ids')) {
    /**
     * @return list<int>
     */
    function gf_ui_trainer_gym_ids(array $trainer): array
    {
        $ids = [];
        if (!empty($trainer['gym_ids']) && is_array($trainer['gym_ids'])) {
            foreach ($trainer['gym_ids'] as $gid) {
                $ids[] = (int) $gid;
            }
        } elseif (!empty($trainer['gyms']) && is_array($trainer['gyms'])) {
            foreach ($trainer['gyms'] as $g) {
                if (is_array($g) && isset($g['id'])) {
                    $ids[] = (int) $g['id'];
                } elseif (is_numeric($g)) {
                    $ids[] = (int) $g;
                }
            }
        } elseif (!empty($trainer['gym_id'])) {
            $ids[] = (int) $trainer['gym_id'];
        }
        $ids = array_values(array_unique(array_filter($ids, static fn ($id) => $id > 0)));
        return $ids;
    }
}

if (!function_exists('gf_ui_trainer_linked_gyms')) {
    /**
     * Resolve gym rows for display. Prefers embedded gyms[]; falls back to lookup map.
     *
     * @param array<int, array> $gymById map id => gym row (optional)
     * @return list<array{id:int,name:string,status?:string,district_name?:string,address?:string}>
     */
    function gf_ui_trainer_linked_gyms(array $trainer, array $gymById = []): array
    {
        $out = [];
        if (!empty($trainer['gyms']) && is_array($trainer['gyms'])) {
            foreach ($trainer['gyms'] as $g) {
                if (!is_array($g) || empty($g['id'])) {
                    continue;
                }
                $out[] = [
                    'id'            => (int) $g['id'],
                    'name'          => (string) ($g['name'] ?? ''),
                    'status'        => (string) ($g['status'] ?? 'active'),
                    'district_name' => (string) ($g['district_name'] ?? ''),
                    'address'       => (string) ($g['address'] ?? ''),
                ];
            }
        }
        if ($out === []) {
            foreach (gf_ui_trainer_gym_ids($trainer) as $gid) {
                if (isset($gymById[$gid])) {
                    $g = $gymById[$gid];
                    $out[] = [
                        'id'            => $gid,
                        'name'          => (string) ($g['name'] ?? ''),
                        'status'        => (string) ($g['status'] ?? 'active'),
                        'district_name' => (string) ($g['district_name'] ?? ''),
                        'address'       => (string) ($g['address'] ?? ''),
                    ];
                } elseif (!empty($trainer['gym_name']) && (int) ($trainer['gym_id'] ?? 0) === $gid) {
                    $out[] = [
                        'id'            => $gid,
                        'name'          => (string) $trainer['gym_name'],
                        'status'        => (string) ($trainer['gym_status'] ?? 'active'),
                        'district_name' => (string) ($trainer['district_name'] ?? ''),
                        'address'       => (string) ($trainer['gym_address'] ?? ''),
                    ];
                }
            }
        }
        return $out;
    }
}

if (!function_exists('gf_ui_trainer_public_gyms')) {
    /**
     * Public-facing gyms: only active (or unknown status treated as active).
     *
     * @return list<array>
     */
    function gf_ui_trainer_public_gyms(array $trainer, array $gymById = []): array
    {
        $gyms = gf_ui_trainer_linked_gyms($trainer, $gymById);
        return array_values(array_filter(
            $gyms,
            static fn (array $g) => ($g['status'] ?? 'active') === 'active'
        ));
    }
}

if (!function_exists('gf_ui_trainer_linked_to_gym')) {
    function gf_ui_trainer_linked_to_gym(array $trainer, int $gymId): bool
    {
        return in_array($gymId, gf_ui_trainer_gym_ids($trainer), true);
    }
}

if (!function_exists('gf_ui_normalize_gym_ids_post')) {
    /**
     * Read gym_ids[] from POST; keep gym_id as first id for backend compatibility.
     *
     * @return array{0: list<int>, 1: int}
     */
    function gf_ui_normalize_gym_ids_post(array $post): array
    {
        $ids = [];
        if (isset($post['gym_ids']) && is_array($post['gym_ids'])) {
            foreach ($post['gym_ids'] as $gid) {
                $ids[] = (int) $gid;
            }
        } elseif (!empty($post['gym_id'])) {
            $ids[] = (int) $post['gym_id'];
        }
        $ids = array_values(array_unique(array_filter($ids, static fn ($id) => $id > 0)));
        $primary = $ids[0] ?? 0;
        return [$ids, $primary];
    }
}

if (!function_exists('gf_ui_trainer_specialty_ids')) {
    /**
     * @return list<int>
     */
    function gf_ui_trainer_specialty_ids(array $trainer): array
    {
        $ids = [];
        if (!empty($trainer['specialty_ids']) && is_array($trainer['specialty_ids'])) {
            foreach ($trainer['specialty_ids'] as $sid) {
                $ids[] = (int) $sid;
            }
        } elseif (!empty($trainer['specialties']) && is_array($trainer['specialties'])) {
            foreach ($trainer['specialties'] as $sp) {
                if (is_array($sp) && isset($sp['id'])) {
                    $ids[] = (int) $sp['id'];
                } elseif (is_numeric($sp)) {
                    $ids[] = (int) $sp;
                }
            }
        } elseif (!empty($trainer['specialty_id'])) {
            $ids[] = (int) $trainer['specialty_id'];
        }
        return array_values(array_unique(array_filter($ids, static fn ($id) => $id > 0)));
    }
}

if (!function_exists('gf_ui_trainer_specialty_names')) {
    /**
     * @param array<int, array> $specById
     * @return list<string>
     */
    function gf_ui_trainer_specialty_names(array $trainer, array $specById = []): array
    {
        $names = [];
        if (!empty($trainer['specialties']) && is_array($trainer['specialties'])) {
            foreach ($trainer['specialties'] as $sp) {
                if (is_array($sp) && !empty($sp['name'])) {
                    $names[] = (string) $sp['name'];
                } elseif (is_string($sp) && $sp !== '') {
                    $names[] = $sp;
                }
            }
        }
        if ($names === [] && !empty($trainer['specialty_names'])) {
            if (is_array($trainer['specialty_names'])) {
                $names = array_map('strval', $trainer['specialty_names']);
            } else {
                $names = array_map('trim', explode(',', (string) $trainer['specialty_names']));
            }
        }
        if ($names === []) {
            foreach (gf_ui_trainer_specialty_ids($trainer) as $sid) {
                if (isset($specById[$sid]['name'])) {
                    $names[] = (string) $specById[$sid]['name'];
                }
            }
        }
        if ($names === [] && !empty($trainer['specialty_name'])) {
            $names[] = (string) $trainer['specialty_name'];
        }
        return array_values(array_filter(array_map('strval', $names), static fn ($n) => trim($n) !== ''));
    }
}

if (!function_exists('gf_ui_normalize_specialty_ids_post')) {
    /**
     * @return array{0: list<int>, 1: int}
     */
    function gf_ui_normalize_specialty_ids_post(array $post): array
    {
        $ids = [];
        if (isset($post['specialty_ids']) && is_array($post['specialty_ids'])) {
            foreach ($post['specialty_ids'] as $sid) {
                $ids[] = (int) $sid;
            }
        } elseif (!empty($post['specialty_id'])) {
            $ids[] = (int) $post['specialty_id'];
        }
        $ids = array_values(array_unique(array_filter($ids, static fn ($id) => $id > 0)));
        $primary = $ids[0] ?? 0;
        return [$ids, $primary];
    }
}
