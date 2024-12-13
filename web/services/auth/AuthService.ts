import { ApiClient } from '../core/ApiClient';
import { ApiResponse } from '../core/types';
import {
  AuthResponse,
  LoginCredentials,
  RegisterData,
  ForgotPasswordData,
  ResetPasswordData,
  User,
} from './types';

// Create a storage helper that's safe to import but only executes in the browser
const storage = {
  get: (key: string) => {
    try {
      return localStorage.getItem(key);
    } catch {
      return null;
    }
  },
  set: (key: string, value: string) => {
    try {
      localStorage.setItem(key, value);
    } catch {
      // Silently fail if localStorage is not available
    }
  },
  remove: (key: string) => {
    try {
      localStorage.removeItem(key);
    } catch {
      // Silently fail if localStorage is not available
    }
  }
};

export class AuthService extends ApiClient {
  private static instance: AuthService;
  private token: string | null = null;

  private constructor() {
    super({
      baseURL: process.env.NEXT_PUBLIC_API_URL || 'http://127.0.0.1:8000/api',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
      },
    });

    // Initialize token if it exists
    const savedToken = storage.get('auth_token');
    if (savedToken) {
      this.setToken(savedToken);
    }
  }

  public static getInstance(): AuthService {
    if (!AuthService.instance) {
      AuthService.instance = new AuthService();
    }
    return AuthService.instance;
  }

  public setToken(token: string | null): void {
    this.token = token;
    if (token) {
      storage.set('auth_token', token);
      this.defaultConfig.headers = {
        ...this.defaultConfig.headers,
        'Authorization': `Bearer ${token}`,
      };
    } else {
      storage.remove('auth_token');
      delete this.defaultConfig.headers['Authorization'];
    }
  }

  public async login(credentials: LoginCredentials): Promise<ApiResponse<AuthResponse>> {
    const response = await this.post<AuthResponse>('login', credentials);
    this.setToken(response.data.token);
    return response;
  }

  public async register(data: RegisterData): Promise<ApiResponse<AuthResponse>> {
    const response = await this.post<AuthResponse>('register', data);
    this.setToken(response.data.token);
    return response;
  }

  public async logout(): Promise<ApiResponse<void>> {
    const response = await this.post<void>('logout');
    this.setToken(null);
    return response;
  }

  public async forgotPassword(data: ForgotPasswordData): Promise<ApiResponse<void>> {
    return this.post<void>('forgot-password', data);
  }

  public async resetPassword(data: ResetPasswordData): Promise<ApiResponse<void>> {
    return this.post<void>('reset-password', data);
  }

  public async getUser(): Promise<ApiResponse<User>> {
    return this.get<User>('me');
  }

  public isAuthenticated(): boolean {
    return !!this.token;
  }
} 