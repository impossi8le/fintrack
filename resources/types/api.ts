export interface BalanceResponse {
  amount: number
}

export interface Transaction {
  id: number
  type: string
  amount: number
  description: string
  created_at: string
}
