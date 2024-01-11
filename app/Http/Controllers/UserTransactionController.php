<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserTransactionCreateRequest;
use App\Http\Requests\UserTransactionUpdateRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserTransactionController extends Controller
{
    public function create(UserTransactionCreateRequest $userTransactionCreateRequest): JsonResponse
    {
        $data = $userTransactionCreateRequest->validated();

        $user = $this->validateUser($data['user_id']);

        return DB::transaction(function () use ($user, $data) {
            try {
                $transaction = new Transaction($data);

                $userBalance = $user->balance;

                switch ($transaction->category) {
                    case 'Pengeluaran':
                        $userBalance->decrement('balance', $transaction->amount);
                        break;
                    case 'Pendapatan':
                        $userBalance->increment('balance', $transaction->amount);
                        break;
                    default:
                        break;
                }

                $transaction->save();

                return response()->json(new TransactionResource($transaction))->setStatusCode(201);
            } catch (\PDOException $e) {
                return response()->json(['error' => $e->getMessage()])->setStatusCode(500);
            }
        });
    }

    public function get(string $userId, Request $request): JsonResponse
    {
        $user = $this->validateUser($userId);

        $date = $request->input('date', null);

        if ($date) {
            $transactions = $user->transactions()
                ->whereYear('date_time', date('Y', strtotime($date)))
                ->whereMonth('date_time', date('m', strtotime($date)))
                ->get();

            return response()->json(TransactionResource::collection($transactions))->setStatusCode(200);
        }

        $transactions = $user->transactions;

        return response()->json(TransactionResource::collection($transactions))->setStatusCode(200);
    }

    public function put(string $transactionId, UserTransactionUpdateRequest $userTransactionUpdateRequest): JsonResponse
    {
        $transaction = $this->validateTransaction($transactionId);

        $data = $userTransactionUpdateRequest->validated();

        return DB::transaction(function () use ($transaction, $data) {
            try {
                $user = $transaction->user;

                $userBalance = $user->balance;

                switch ($transaction->category) {
                    case 'Pengeluaran':
                        $userBalance->increment('balance', $transaction->amount);
                        $userBalance->decrement('balance', $data["amount"]);
                        break;
                    case 'Pendapatan':
                        $userBalance->decrement('balance', $transaction->amount);
                        $userBalance->increment('balance', $data["amount"]);
                        break;
                    default:
                        break;
                }

                $transaction->fill($data);
                $transaction->save();

                return response()->json(new TransactionResource($transaction))->setStatusCode(200);
            } catch (\PDOException $e) {
                return response()->json(['error' => $e->getMessage()])->setStatusCode(500);
            }
        });
    }

    public function delete(string $transactionId): JsonResponse
    {
        return DB::transaction(function () use ($transactionId) {
            try {
                $transaction = $this->validateTransaction($transactionId);

                $user = $transaction->user;

                $userBalance = $user->balance;

                switch ($transaction->category) {
                    case 'Pengeluaran':
                        $userBalance->increment('balance', $transaction->amount);
                        break;
                    case 'Pendapatan':
                        $userBalance->decrement('balance', $transaction->amount);
                        break;
                    default:
                        break;
                }

                $transaction->delete();

                return response()->json([
                    "message" => "transaksi berhasil dihapus"
                ])->setStatusCode(200);
            } catch (\PDOException $e) {
                return response()->json(['error' => $e->getMessage()])->setStatusCode(500);
            }
        });
    }

    private function validateUser(string $userId): Model
    {
        $user = User::query()->find($userId);

        if (!$user) {
            throw new HttpResponseException(response([
                "errors" => "user tidak ditemukan"
            ], 404));
        }

        return $user;
    }

    private function validateTransaction(string $transactionId): Model
    {
        $transaction = Transaction::query()->find($transactionId);

        if (!$transaction) {
            throw new HttpResponseException(response([
                "errors" => "transaksi tidak ditemukan"
            ], 404));
        }

        return $transaction;
    }
}
