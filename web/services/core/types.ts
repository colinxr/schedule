export interface ApiResponse<T> {
  data: T;
  message?: string;
}

export interface ApiError {
  message: string;
  errors?: Record<string, string[]>;
  status?: number;
}

export interface RequestConfig extends RequestInit {
  params?: Record<string, string>;
  timeout?: number;
}

export interface ApiClientConfig {
  baseURL: string;
  timeout?: number;
  headers?: Record<string, string>;
}

export interface ApiHeaders extends Record<string, string> {
  'Content-Type'?: string;
  'Accept'?: string;
  'Authorization'?: string;
  'X-Requested-With'?: string;
} 