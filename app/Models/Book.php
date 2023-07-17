<?php

namespace App\Models;

use App\Models\BankAccount;
use App\Models\Category;
use App\Traits\Models\ConstantsGetter;
use App\Transaction;
use App\User;
use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    use ConstantsGetter;

    const STATUS_INACTIVE = 0;
    const STATUS_ACTIVE = 1;

    protected $fillable = ['name', 'description', 'status_id', 'creator_id', 'bank_account_id'];

    public function creator()
    {
        return $this->belongsTo(User::class)->withDefault(['name' => __('app.system')]);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    public function getNameLabelAttribute()
    {
        return '<span class="badge badge-pill badge-secondary">'.$this->name.'</span>';
    }

    public function getStatusAttribute()
    {
        return $this->status_id == static::STATUS_INACTIVE ? __('app.inactive') : __('app.active');
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class)->withDefault(['name' => __('book.no_bank_account')]);
    }
}
