<?php

namespace App\Http\Controllers;

use App\Models\BankAccountBalance;
use App\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ReportsController extends Controller
{
    public function index(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $month = $request->get('month', date('m'));
        $yearMonth = $this->getYearMonth();
        $groupedTransactions = $this->getTansactions($yearMonth)->groupBy('in_out');
        $incomeCategories = isset($groupedTransactions[1]) ? $groupedTransactions[1]->pluck('category')->unique()->filter() : collect([]);
        $spendingCategories = isset($groupedTransactions[0]) ? $groupedTransactions[0]->pluck('category')->unique()->filter() : collect([]);
        $lastMonthDate = Carbon::parse($yearMonth.'-01')->subDay();
        $currentMonthEndDate = Carbon::parse(Carbon::parse($yearMonth.'-01')->format('Y-m-t'));
        $lastBankAccountBalanceOfTheMonth = $this->getLastBankAccountBalance($currentMonthEndDate);
        $lastMonthBalance = balance($lastMonthDate->format('Y-m-d'));

        $prevMonthDate = Carbon::parse($yearMonth.'-10')->subMonth();
        $nextMonthDate = Carbon::parse($yearMonth.'-10')->addMonth();

        return view('reports.index', compact(
            'year', 'month', 'yearMonth', 'groupedTransactions', 'incomeCategories',
            'spendingCategories', 'lastBankAccountBalanceOfTheMonth', 'lastMonthDate',
            'lastMonthBalance', 'currentMonthEndDate', 'prevMonthDate', 'nextMonthDate'
        ));
    }

    public function inOut(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $month = $request->get('month', date('m'));
        $yearMonth = $this->getYearMonth();
        $currentMonthEndDate = Carbon::parse(Carbon::parse($yearMonth.'-01')->format('Y-m-t'));
        $prevMonthDate = Carbon::parse($yearMonth.'-10')->subMonth();
        $nextMonthDate = Carbon::parse($yearMonth.'-10')->addMonth();

        $groupedTransactions = $this->getTansactions($yearMonth)->groupBy('in_out');
        $incomeCategories = isset($groupedTransactions[1]) ? $groupedTransactions[1]->pluck('category')->unique()->filter() : collect([]);
        $spendingCategories = isset($groupedTransactions[0]) ? $groupedTransactions[0]->pluck('category')->unique()->filter() : collect([]);

        return view('reports.in_out', compact(
            'year', 'month', 'yearMonth', 'currentMonthEndDate', 'prevMonthDate', 'nextMonthDate',
            'groupedTransactions', 'incomeCategories', 'spendingCategories'
        ));
    }

    public function inWeeks(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $month = $request->get('month', date('m'));
        $yearMonth = $this->getYearMonth();
        $groupedTransactions = $this->getWeeklyGroupedTransactions($yearMonth);
        $currentMonthEndDate = Carbon::parse(Carbon::parse($yearMonth.'-01')->format('Y-m-t'));

        $prevMonthDate = Carbon::parse($yearMonth.'-10')->subMonth();
        $nextMonthDate = Carbon::parse($yearMonth.'-10')->addMonth();

        return view('reports.in_weeks', compact(
            'year', 'month', 'yearMonth', 'groupedTransactions',
            'currentMonthEndDate', 'prevMonthDate', 'nextMonthDate'
        ));
    }

    private function getWeeklyGroupedTransactions(string $yearMonth): Collection
    {
        $lastMonthDate = Carbon::parse($yearMonth.'-01')->subDay();

        $transactions = $this->getTansactions($yearMonth);
        $groupedTransactions = collect([]);
        $lastWeekDate = null;
        foreach (get_date_range_per_week($yearMonth) as $weekNumber => $weekDates) {
            $weekTransactions = $transactions->filter(function ($transaction) use ($weekDates) {
                return in_array($transaction->date, $weekDates);
            });
            $lastWeekDate = $lastWeekDate ?: $lastMonthDate;
            if (!$weekTransactions->isEmpty()) {
                $firstBalance = new Transaction([
                    'date' => null,
                    'description' => 'Saldo per '.$lastWeekDate->isoFormat('D MMMM Y'),
                    'in_out' => 1,
                    'amount' => balance($lastWeekDate->format('Y-m-d')),
                ]);
                $firstBalance->is_strong = 1;
                $weekTransactions->prepend($firstBalance);
                $groupedTransactions->put($weekNumber, $weekTransactions->groupBy('day_name'));
                $lastWeekDate = Carbon::parse($weekTransactions->last()->date);
            }
        }

        return collect($groupedTransactions);
    }

    private function getLastBankAccountBalance(Carbon $currentMonthEndDate): BankAccountBalance
    {
        $currentMonthBalance = BankAccountBalance::where('date', '<=', $currentMonthEndDate->format('Y-m-d'))
            ->orderBy('date', 'desc')
            ->first();
        if ($currentMonthBalance) {
            return $currentMonthBalance;
        }

        return new BankAccountBalance([
            'date' => $currentMonthEndDate->format('Y-m-d'),
            'amount' => 0,
        ]);
    }
}
