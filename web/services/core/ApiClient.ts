import { ApiClientConfig, ApiResponse, RequestConfig, ApiHeaders } from './types';
import { ApiError } from './ApiError';

export class ApiClient {
  protected readonly baseURL: string;
  protected readonly defaultConfig: RequestConfig & { headers: ApiHeaders };

  constructor(config: ApiClientConfig) {
    this.baseURL = (config.baseURL || process.env.NEXT_PUBLIC_API_BASE_URL).replace(/\/$/, '');
    this.defaultConfig = {
      timeout: config.timeout || 10000,
      credentials: 'include',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        ...config.headers,
      },
    };

    if (config.requestInterceptor) {
      this.addRequestInterceptor(config.requestInterceptor);
    }

    if (config.responseInterceptor) {
      this.addResponseInterceptor(config.responseInterceptor);
    }
  }

  private requestInterceptors: ((config: RequestConfig) => RequestConfig)[] = [];
  
  public addRequestInterceptor(interceptor: (config: RequestConfig) => RequestConfig) {
    this.requestInterceptors.push(interceptor);
  }

  private responseInterceptors: ((response: ApiResponse<any>) => ApiResponse<any>)[] = [];
  
  public addResponseInterceptor(interceptor: (response: ApiResponse<any>) => ApiResponse<any>) {
    this.responseInterceptors.push(interceptor);
  }

  protected async request<T>(endpoint: string, config?: RequestConfig): Promise<ApiResponse<T>> {
    let requestConfig = this.mergeConfig(config);

    for (const interceptor of this.requestInterceptors) {
      requestConfig = interceptor(requestConfig);
    }

    const url = this.createUrl(endpoint, requestConfig.params);
    const controller = new AbortController();

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

      let data = await response.json() as ApiResponse<T>;

      for (const interceptor of this.responseInterceptors) {
        data = interceptor(data);
      }

      return data;
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

  private mergeConfig(config?: RequestConfig): RequestConfig & { headers: ApiHeaders } {
    return {
      ...this.defaultConfig,
      ...config,
      headers: {
        ...this.defaultConfig.headers,
        ...(config?.headers as ApiHeaders),
      },
    };
  }
} 