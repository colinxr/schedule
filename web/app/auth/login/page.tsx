"use client";

import { AuthForm } from "@/app/components/auth/AuthForm";
import { FormField, FormItem, FormLabel, FormControl, FormMessage } from "@/components/ui/form";
import { Input } from "@/components/ui/input";
import { Alert, AlertDescription } from "@/components/ui/alert";
import { z } from "zod";
import Link from "next/link";
import { useRouter, useSearchParams } from "next/navigation";
import { useEffect, useState } from "react";
import { AuthService } from "@/services/auth/AuthService";

const loginSchema = z.object({
  email: z.string().email("Please enter a valid email address"),
  password: z.string().min(1, "Password is required"),
});

export default function LoginPage() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const [showSuccessMessage, setShowSuccessMessage] = useState(false);
  const authService = AuthService.getInstance();

  useEffect(() => {
    if (searchParams?.get("registered") === "true") {
      setShowSuccessMessage(true);
      const timer = setTimeout(() => setShowSuccessMessage(false), 5000);
      return () => clearTimeout(timer);
    }
  }, [searchParams]);

  const handleLogin = async (data: z.infer<typeof loginSchema>) => {
    try {
      const response = await authService.login(data);
      
      // Store token in localStorage for persistence
      localStorage.setItem('token', response.data.token);
      
      router.push("/dashboard");
    } catch (error) {
      if (error instanceof Error) {
        throw new Error(error.message);
      }
      throw new Error("Login failed");
    }
  };

  return (
    <div className="w-full max-w-md space-y-4">
      {showSuccessMessage && (
        <Alert className="bg-green-900/50 border-green-500/50 text-green-200">
          <AlertDescription>
            Registration successful! Please log in with your credentials.
          </AlertDescription>
        </Alert>
      )}
      <AuthForm
        title="Welcome Back"
        description="Sign in to your artist account"
        schema={loginSchema}
        onSubmit={handleLogin}
        submitText="Sign In"
        footer={
          <div className="space-y-2 text-center">
            <p>
              <Link
                href="/auth/forgot-password"
                className="text-blue-600 hover:text-blue-700 text-sm hover:underline"
              >
                Forgot your password?
              </Link>
            </p>
            <p className="text-gray-500">
              Don't have an account?{" "}
              <Link href="/auth/register" className="text-blue-600 hover:text-blue-700 text-sm hover:underline">
                Sign up
              </Link>
            </p>
          </div>
        }
      >
        <FormField
          name="email"
          render={({ field }) => (
            <FormItem>
              <FormLabel>Email</FormLabel>
              <FormControl>
                <Input 
                  type="email" 
                  placeholder="john@example.com" 
                  className="form-input"
                  {...field} 
                />
              </FormControl>
              <FormMessage className="form-error" />
            </FormItem>
          )}
        />
        <FormField
          name="password"
          render={({ field }) => (
            <FormItem>
              <FormLabel>Password</FormLabel>
              <FormControl>
                <Input 
                  type="password" 
                  className="form-input"
                  {...field} 
                />
              </FormControl>
              <FormMessage className="form-error" />
            </FormItem>
          )}
        />
      </AuthForm>
    </div>
  );
} 