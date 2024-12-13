import { create } from 'zustand';
import { ConversationSelectionStore } from '@/stores/conversationSelectionStore';

export const useConversationSelection = create<ConversationSelectionStore>((set) => ({
  isSelected: false,
  setSelected: (selected) => set({ isSelected: selected }),
})); 