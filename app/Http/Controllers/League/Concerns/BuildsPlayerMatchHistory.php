<?php

namespace App\Http\Controllers\League\Concerns;

use App\Models\Championship;
use App\Models\Cup;
use App\Models\FriendlyMatch;
use App\Models\User;

trait BuildsPlayerMatchHistory
{
    private function matchHistoryFor(User $player)
    {
        return collect()
            ->concat($this->championshipMatchesFor($player))
            ->concat($this->cupMatchesFor($player))
            ->concat($this->friendlyMatchesFor($player))
            ->sortByDesc('played_at')
            ->values();
    }

    private function championshipMatchesFor(User $player)
    {
        return Championship::with([
            'matches.homeEntry.user',
            'matches.homeEntry.team',
            'matches.awayEntry.user',
            'matches.awayEntry.team',
        ])
            ->get()
            ->flatMap(function ($championship) use ($player) {
                return $championship->matches
                    ->filter->isPlayed()
                    ->filter(fn ($match) => in_array($player->id, [$match->homeEntry->user_id, $match->awayEntry->user_id]))
                    ->map(fn ($match) => $this->describeMatch(
                        'c'.$match->id,
                        $championship->name,
                        $match->played_at,
                        $player,
                        $match->homeEntry->user_id,
                        $match->homeEntry->user->name,
                        $match->homeEntry->team,
                        $match->awayEntry->user_id,
                        $match->awayEntry->user->name,
                        $match->awayEntry->team,
                        $match->home_score,
                        $match->away_score,
                    ));
            });
    }

    private function cupMatchesFor(User $player)
    {
        return Cup::with([
            'matches.homeEntry.user',
            'matches.homeEntry.team',
            'matches.awayEntry.user',
            'matches.awayEntry.team',
        ])
            ->get()
            ->flatMap(function ($cup) use ($player) {
                return $cup->matches
                    ->filter->isPlayed()
                    ->filter(fn ($match) => in_array($player->id, [$match->homeEntry->user_id, $match->awayEntry->user_id]))
                    ->map(fn ($match) => $this->describeMatch(
                        'k'.$match->id,
                        $cup->name,
                        $match->played_at,
                        $player,
                        $match->homeEntry->user_id,
                        $match->homeEntry->user->name,
                        $match->homeEntry->team,
                        $match->awayEntry->user_id,
                        $match->awayEntry->user->name,
                        $match->awayEntry->team,
                        $match->home_score,
                        $match->away_score,
                    ));
            });
    }

    private function friendlyMatchesFor(User $player)
    {
        return FriendlyMatch::with(['homeUser', 'awayUser', 'homeTeam', 'awayTeam'])
            ->where('home_user_id', $player->id)
            ->orWhere('away_user_id', $player->id)
            ->get()
            ->map(fn ($match) => $this->describeMatch(
                'f'.$match->id,
                'Meci amical',
                $match->played_at,
                $player,
                $match->home_user_id,
                $match->homeUser->name,
                $match->homeTeam,
                $match->away_user_id,
                $match->awayUser->name,
                $match->awayTeam,
                $match->home_score,
                $match->away_score,
            ));
    }

    private function describeMatch(
        string $id,
        string $competitionName,
        $playedAt,
        User $player,
        int $homeUserId,
        string $homeUserName,
        $homeTeam,
        int $awayUserId,
        string $awayUserName,
        $awayTeam,
        int $homeScore,
        int $awayScore,
    ): array {
        $isHome = $homeUserId === $player->id;

        $myScore = $isHome ? $homeScore : $awayScore;
        $opponentScore = $isHome ? $awayScore : $homeScore;

        return [
            'id' => $id,
            'competition_name' => $competitionName,
            'played_at' => $playedAt,
            'opponent_id' => $isHome ? $awayUserId : $homeUserId,
            'opponent_name' => $isHome ? $awayUserName : $homeUserName,
            'my_team' => $isHome ? $homeTeam : $awayTeam,
            'opponent_team' => $isHome ? $awayTeam : $homeTeam,
            'my_score' => $myScore,
            'opponent_score' => $opponentScore,
            'result' => $myScore > $opponentScore ? 'W' : ($myScore < $opponentScore ? 'L' : 'D'),
        ];
    }
}
