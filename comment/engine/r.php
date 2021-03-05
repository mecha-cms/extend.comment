<?php

namespace _\lot\x\comment\links {
    // Add comment reply link
    function reply($a, $page, $deep) {
        $state = \State::get('x.comment', true);
        $k = $page->get('state.comment') ?? $state['page']['state']['comment'] ?? 1;
        if ($deep < ($state['page']['deep'] ?? 0) && (1 === $k || true === $k)) {
            $id = $this->name;
            $a['reply'] = [
                0 => 'a',
                1 => \i('Reply'),
                2 => [
                    'class' => 'comment-link js:reply',
                    'data-parent' => $id,
                    'href' => $GLOBALS['url']->query('&', ['parent' => $id]) . '#' . $state['anchor'][0],
                    'rel' => 'nofollow',
                    'target' => \sprintf($state['anchor'][2], $this->id),
                    'title' => \To::text(i('Reply to %s', (string) $this->author))
                ]
            ];
        }
        return $a;
    }
    \Hook::set('comment:tasks', __NAMESPACE__ . "\\reply", 10);
}

namespace _\lot\x\comment {
    // Build link(s) from array
    function hooks(array $in, array $lot = [], $comment) {
        $out = [];
        foreach ($in as $v) {
            if (\is_array($v)) {
                $out[] = new \HTML(\array_replace(['a', "", []], $v));
            } else if (\is_callable($v)) {
                $out[] = \fire($v, $lot, $comment);
            } else {
                $out[] = $v;
            }
        }
        return $out;
    }
}
