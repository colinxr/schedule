"use client";

import { AuthForm } from "@/app/components/auth/AuthForm";
import { FormField, FormItem, FormLabel, FormControl, FormMessage } from "@/components/ui/form";
import { Input } from "@/components/ui/input";
import { Alert, AlertDescription } from "@/components/ui/alert";
import { z } from "zod";
import Link from "next/link";
import { useState } from "react";
import { AuthService } from "@/services/auth/AuthService";

const forgotPasswordSchema = z.object({
  email: z.string().email("Please enter a valid email address"),
});

export default function ForgotPasswordPage() {
  const [isEmailSent, setIsEmailSent] = useState(false);
  const authService = AuthService.getInstance();

  const handleForgotPassword = async (data: z.infer<typeof forgotPasswordSchema>) => {
    try {
      await authService.forgotPassword(data);
      setIsEmailSent(true);
    } catch (error) {
      if (error instanceof Error) {
        throw new Error(error.message);
      }
      throw new Error("Failed to send reset email");
    }
  };

  if (isEmailSent) {
    return (
      <div className="w-full max-w-md">
        <Alert className="bg-green-900/50 border-green-500/50 text-green-200">
          <AlertDescription className="text-center">
            If an account exists with that email address, you will receive password reset instructions.
          </AlertDescription>
        </Alert>
        <div className="mt-4 text-center">
          <Link href="/auth/login" className="text-blue-600 hover:text-blue-700 text-sm hover:underline">
            Return to login
          </Link>
        </div>
      </div>
    );
  }

  return (
    <div className="w-full max-w-md space-y-4">
      <AuthForm
        title="Forgot Password"
        description="Enter your email address and we'll send you instructions to reset your password."
        schema={forgotPasswordSchema}
        onSubmit={handleForgotPassword}
        submitText="Send Reset Link"
        footer={
          <p className="text-gray-500">
            Remember your password?{" "}
            <Link href="/auth/login" className="text-blue-600 hover:text-blue-700 text-sm hover:underline">
              Sign in
            </Link>
          </p>
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
      </AuthForm>
    </div>
  );
} 