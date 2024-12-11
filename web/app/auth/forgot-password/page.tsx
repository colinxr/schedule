"use client";

import { AuthForm } from "@/app/components/auth/AuthForm";
import { FormField, FormItem, FormLabel, FormControl, FormMessage } from "@/components/ui/form";
import { Input } from "@/components/ui/input";
import { Alert, AlertDescription } from "@/components/ui/alert";
import { z } from "zod";
import Link from "next/link";
import { useState } from "react";

const forgotPasswordSchema = z.object({
  email: z.string().email("Please enter a valid email address"),
});

export default function ForgotPasswordPage() {
  const [isEmailSent, setIsEmailSent] = useState(false);

  const handleForgotPassword = async (data: z.infer<typeof forgotPasswordSchema>) => {
    try {
      const response = await fetch("/api/forgot-password", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(data),
      });

      if (!response.ok) {
        const error = await response.json();
        throw new Error(error.message || "Failed to send reset email");
      }

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
          <Link href="/auth/login" className="text-sm hover:underline">
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
          <p className="text-gray-400">
            Remember your password?{" "}
            <Link href="/auth/login" className="text-sm hover:underline">
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