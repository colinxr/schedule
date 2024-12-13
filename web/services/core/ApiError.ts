import { ApiError as IApiError } from './types';

export class ApiError extends Error {
  public readonly errors?: Record<string, string[]>;
  public readonly status?: number;

  constructor(error: IApiError) {
    super(error.message);
    this.name = 'ApiError';
    this.errors = error.errors;
    this.status = error.status;

    // Ensure proper prototype chain for instanceof checks
    Object.setPrototypeOf(this, ApiError.prototype);
  }

  public static fromResponse(response: Response, data: any): ApiError {
    return new ApiError({
      message: data.message || 'An unexpected error occurred',
      errors: data.errors,
      status: response.status,
    });
  }

  public static fromError(error: Error): ApiError {
    return new ApiError({
      message: error.message || 'An unexpected error occurred',
      status: 500,
    });
  }
} 