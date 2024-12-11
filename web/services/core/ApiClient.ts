import { ApiClientConfig, ApiResponse, RequestConfig } from './types';
import { ApiError } from './ApiError';

export class ApiClient {
  protected readonly baseURL: string;
  protected readonly defaultConfig: RequestConfig;

  constructor(config: ApiClientConfig) {
    this.baseURL = config.baseURL.replace(/\/$/, ''); // Remove trailing slash
    this.defaultConfig = {
      timeout: config.timeout || 10000,
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        ...config.headers,
      },
    };
  }

  protected async request<T>(endpoint: string, config?: RequestConfig): Promise<ApiResponse<T>> {
    const url = this.createUrl(endpoint, config?.params);
    const requestConfig = this.mergeConfig(config);
    const controller = new AbortController();

    // Set up timeout
    const timeout = setTimeout(() => {
      controller.abort();
    }, requestConfig.timeout || this.defaultConfig.timeout);

    try {
      const response = await fetch(url, {
        ...requestConfig,
        signal: controller.signal,
      });

      clearTimeout(timeout);

      if (!response.ok) {
        const data = await response.json();
        throw ApiError.fromResponse(response, data);
      }

      const data = await response.json();
      return data as ApiResponse<T>;
    } catch (error) {
      clearTimeout(timeout);

      if (error instanceof ApiError) {
        throw error;
      }

      if (error instanceof Error) {
        throw ApiError.fromError(error);
      }

      throw new ApiError({
        message: 'An unexpected error occurred',
        status: 500,
      });
    }
  }

  protected async get<T>(endpoint: string, config?: RequestConfig): Promise<ApiResponse<T>> {
    return this.request<T>(endpoint, { ...config, method: 'GET' });
  }

  protected async post<T>(endpoint: string, data?: unknown, config?: RequestConfig): Promise<ApiResponse<T>> {
    return this.request<T>(endpoint, {
      ...config,
      method: 'POST',
      body: data ? JSON.stringify(data) : undefined,
    });
  }

  protected async put<T>(endpoint: string, data?: unknown, config?: RequestConfig): Promise<ApiResponse<T>> {
    return this.request<T>(endpoint, {
      ...config,
      method: 'PUT',
      body: data ? JSON.stringify(data) : undefined,
    });
  }

  protected async delete<T>(endpoint: string, config?: RequestConfig): Promise<ApiResponse<T>> {
    return this.request<T>(endpoint, { ...config, method: 'DELETE' });
  }

  private createUrl(endpoint: string, params?: Record<string, string>): string {
    const url = new URL(`${this.baseURL}/${endpoint.replace(/^\//, '')}`);
    
    if (params) {
      Object.entries(params).forEach(([key, value]) => {
        url.searchParams.append(key, value);
      });
    }

    return url.toString();
  }

  private mergeConfig(config?: RequestConfig): RequestConfig {
    return {
      ...this.defaultConfig,
      ...config,
      headers: {
        ...this.defaultConfig.headers,
        ...config?.headers,
      },
    };
  }
} 