<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Models\StressWord;
use App\Services\Gamification;

final class TrainerController extends Controller
{
    private const VOWELS = ['а','е','ё','и','о','у','ы','э','ю','я'];

    public function __construct()
    {
        \App\Core\Auth::requireAuth();
    }

    public function index(Request $req, array $params = []): void
    {
        $this->view('trainer/index', [
            'title'   => 'Тренажёры',
            'summary' => StressWord::summary($this->user()['id']),
        ]);
    }

    public function stress(Request $req, array $params = []): void
    {
        $this->view('trainer/stress', ['title' => 'Ударения']);
    }

    /** AJAX: пачка слов. Ответ (индекс ударения) клиенту не отдаём. */
    public function stressBatch(Request $req, array $params = []): void
    {
        $exclude = array_slice(array_filter(array_map('intval', (array) $req->input('exclude', []))), -60);
        $words   = StressWord::batchFor($this->user()['id'], 12, $exclude);

        $payload = array_map(function (array $w) {
            $letters = self::split($w['word']);
            return [
                'id'      => (int) $w['id'],
                'letters' => $letters,
                'vowels'  => self::vowelPositions($letters),
                'hint'    => $w['hint'],
            ];
        }, $words);

        $this->json(['words' => $payload]);
    }

    /** AJAX: проверка выбранной буквы. */
    public function stressCheck(Request $req, array $params = []): void
    {
        $wordId = (int) $req->input('word_id');
        $picked = (int) $req->input('index');
        $word   = StressWord::find($wordId);

        if (!$word || !$word['is_active']) {
            $this->json(['error' => 'Слово недоступно'], 404);
        }

        $correct = $picked === (int) $word['stress_index'];
        StressWord::record($this->user()['id'], $wordId, $correct);

        $reward = $correct ? Gamification::award($this->user()['id'], 6) : null;

        $this->json([
            'correct'      => $correct,
            'stress_index' => (int) $word['stress_index'],
            'reward'       => $reward,
        ]);
    }

    /** Корректное разбиение слова на буквы (UTF-8). */
    private static function split(string $word): array
    {
        return preg_split('//u', $word, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }

    /** Индексы гласных — только они кликабельны в тренажёре. */
    private static function vowelPositions(array $letters): array
    {
        $out = [];
        foreach ($letters as $i => $ch) {
            if (in_array(mb_strtolower($ch), self::VOWELS, true)) {
                $out[] = $i;
            }
        }
        return $out;
    }
}
