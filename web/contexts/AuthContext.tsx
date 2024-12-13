'use client';

import { createContext, useContext, useEffect, useState } from 'react';
import { AuthService } from '@/services/auth/AuthService';

interface User {
  id: number;
  first_name: string;
  last_name: string;
  email: string;
  role: string;
  profile?: {
    // Add profile fields as needed
    [key: string]: any;
  };
}

interface AuthContextType {
  isAuthenticated: boolean;
  user: User | null;
  login: (email: string, password: string) => Promise<void>;
  logout: () => Promise<void>;
  loading: boolean;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(true);
  const authService = AuthService.getInstance();

  useEffect(() => {
    const checkAuth = async () => {
      try {
        if (authService.isAuthenticated()) {
          const response = await authService.getUser();
          setUser(response.data);
        }
      } catch (error) {
        // Type guard for error handling
        if (error instanceof Error) {
          console.error('Auth check failed:', error.message);
        }
        authService.setToken(null);
      } finally {
        setLoading(false);
      }
    };

    checkAuth();
  }, []);

  const login = async (email: string, password: string) => {
    try {
      const response = await authService.login({ email, password });
      setUser(response.data.user);
    } catch (error) {
      // Type guard for error handling
      if (error instanceof Error) {
        throw new Error(`Login failed: ${error.message}`);
      }
      throw new Error('Login failed');
    }
  };

  const logout = async () => {
    try {
      await authService.logout();
      setUser(null);
    } catch (error) {
      // Type guard for error handling
      if (error instanceof Error) {
        console.error('Logout failed:', error.message);
      }
      // Still clear user even if logout API call fails
      setUser(null);
    }
  };

  const value: AuthContextType = {
    isAuthenticated: !!user,
    user,
    login,
    logout,
    loading
  };

  return (
    <AuthContext.Provider value={value}>
      {children}
    </AuthContext.Provider>
  );
}

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (context === undefined) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
}; 